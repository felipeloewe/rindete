<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\CategoriaModel;
use App\Models\GastoModel;
use App\Models\UsuarioModel;
use CodeIgniter\HTTP\Files\UploadedFile;

class GastoServicio
{
    public const CUPO_MENSUAL = 50;

    /** @var list<int> */
    private array $ultimasEscrituras = [];

    public function asegurarCategorias(array $empresa): void
    {
        $pais = Paises::pais((string) $empresa['pais']);
        CategoriasBase::sembrar((int) $empresa['id'], $pais['moneda'] ?? 'USD');
    }

    /**
     * @param array<string, mixed> $usuario
     * @param array<string, mixed> $empresa
     * @param array<string, mixed> $input
     *
     * @return array{ok: bool, errores: list<string>, gasto?: array<string, mixed>}
     */
    public function crear(array $usuario, array $empresa, array $input, ?UploadedFile $archivo): array
    {
        if ($this->cupo((int) $empresa['id']) >= self::CUPO_MENSUAL) {
            return ['ok' => false, 'errores' => ['El plan incluye ' . self::CUPO_MENSUAL . ' documentos al mes.']];
        }

        return $this->guardar($usuario, $empresa, null, $input, $archivo);
    }

    /**
     * @param array<string, mixed> $usuario
     * @param array<string, mixed> $empresa
     * @param array<string, mixed> $actual
     * @param array<string, mixed> $input
     *
     * @return array{ok: bool, errores: list<string>, gasto?: array<string, mixed>}
     */
    public function actualizar(array $usuario, array $empresa, array $actual, array $input, ?UploadedFile $archivo): array
    {
        if ($this->bloqueado($actual)) {
            return ['ok' => false, 'errores' => ['Este gasto está en una rendición y no se puede editar.']];
        }

        return $this->guardar($usuario, $empresa, $actual, $input, $archivo);
    }

    /**
     * @param array<string, mixed> $gasto
     *
     * @return array{ok: bool, errores: list<string>}
     */
    public function eliminar(array $gasto): array
    {
        if ($this->bloqueado($gasto)) {
            return ['ok' => false, 'errores' => ['Este gasto ya está en una rendición y no se puede eliminar.']];
        }

        (new Comprobante())->borrar((string) $gasto['comprobante']);
        model(GastoModel::class)->delete($gasto['id']);
        $this->reevaluar((int) $gasto['empresa_id'], null, $gasto);

        return ['ok' => true, 'errores' => []];
    }

    /**
     * Reevaluates policy flags. With $ancla/$previo, only the touched expense and duplicate
     * candidates are rewritten. With $categoriaId alone, only expenses in that category.
     * A bare empresa id still reevaluates the whole company (legacy / full refresh).
     *
     * @param array<string, mixed>|null $ancla  Gasto after create/update (still in DB)
     * @param array<string, mixed>|null $previo Snapshot before update/delete
     *
     * @return int Number of gasto rows written
     */
    public function reevaluar(int $empresaId, ?array $ancla = null, ?array $previo = null, ?int $categoriaId = null): int
    {
        $this->ultimasEscrituras = [];
        $empresa                 = model(\App\Models\EmpresaModel::class)->find($empresaId);
        if (! is_array($empresa)) {
            return 0;
        }

        $categorias = $this->indiceCategorias($empresaId);
        $evaluador  = new GastoEvaluador();

        if ($ancla !== null || $previo !== null) {
            $aEvaluar = $this->gastosAfectados($empresaId, $empresa, $ancla, $previo);
            $universo = $this->universoDuplicados($empresaId, $empresa, $aEvaluar);
        } elseif ($categoriaId !== null) {
            $aEvaluar = model(GastoModel::class)
                ->where('empresa_id', $empresaId)
                ->where('categoria_id', $categoriaId)
                ->findAll();
            // Need company-wide peers so duplicate flags are not cleared by a tope-only pass.
            $universo = model(GastoModel::class)->where('empresa_id', $empresaId)->findAll();
        } else {
            $aEvaluar = model(GastoModel::class)->where('empresa_id', $empresaId)->findAll();
            $universo = $aEvaluar;
        }

        $cambios = [];
        foreach ($aEvaluar as $gasto) {
            $categoria = $categorias[(int) $gasto['categoria_id']] ?? null;
            $nuevo     = $evaluador->evaluar($gasto, $empresa, $categoria, $universo, (int) $gasto['id']);
            $campos    = [
                'estado'            => $nuevo['estado'],
                'duplicado'         => $nuevo['duplicado'],
                'fuera_politica'    => $nuevo['fuera_politica'],
                'sin_comprobante'   => $nuevo['sin_comprobante'],
                'fiscal_incompleto' => $nuevo['fiscal_incompleto'],
            ];
            if ($this->flagsDistintos($gasto, $campos)) {
                $campos['id'] = (int) $gasto['id'];
                $cambios[]    = $campos;
            }
        }

        return $this->aplicarCambios($cambios);
    }

    /**
     * IDs written by the last reevaluar() call (for tests).
     *
     * @return list<int>
     */
    public function ultimasEscrituras(): array
    {
        return $this->ultimasEscrituras;
    }

    public function bloqueado(array $gasto): bool
    {
        return in_array((string) $gasto['estado'], ['en_rendicion', 'aprobado'], true);
    }

    /**
     * @param array<string, mixed>      $usuario
     * @param array<string, mixed>      $empresa
     * @param array<string, mixed>|null $actual
     * @param array<string, mixed>      $input
     *
     * @return array{ok: bool, errores: list<string>, gasto?: array<string, mixed>}
     */
    private function guardar(array $usuario, array $empresa, ?array $actual, array $input, ?UploadedFile $archivo): array
    {
        $concepto  = trim((string) ($input['concepto'] ?? ''));
        $proveedor = trim((string) ($input['proveedor'] ?? ''));
        $fecha     = Montos::fecha((string) ($input['fecha'] ?? ''));
        $monto     = Montos::parse((string) ($input['monto'] ?? ''));
        $pais      = Paises::pais((string) $empresa['pais']);
        $tipo      = trim((string) ($input['tipo_doc'] ?? ''));
        $categoria = $this->categoriaValida((int) ($input['categoria_id'] ?? 0), (int) $empresa['id'], $actual);
        $errores   = [];

        if ($concepto === '' || $proveedor === '' || strlen($concepto) > 140 || strlen($proveedor) > 140) {
            $errores[] = 'Escribe el concepto y el proveedor.';
        }
        if ($fecha === null) {
            $errores[] = 'La fecha no es válida.';
        }
        if ($monto === null) {
            $errores[] = 'El monto no es válido.';
        }
        if ($categoria === null) {
            $errores[] = 'Elige una categoría activa.';
        }
        if ($tipo !== '' && ($pais === null || ! in_array($tipo, $pais['docs'], true))) {
            $errores[] = 'El tipo de documento no corresponde al país.';
        }
        if ($errores !== []) {
            return ['ok' => false, 'errores' => $errores];
        }

        $comprobante = (string) ($actual['comprobante'] ?? '');
        if (! empty($input['quitar_comprobante'])) {
            (new Comprobante())->borrar($comprobante);
            $comprobante = '';
        }
        $subida = (new Comprobante())->guardar($archivo, (int) $empresa['id']);
        if (! $subida['ok']) {
            return ['ok' => false, 'errores' => [$subida['error']]];
        }
        if ($subida['nombre'] !== '') {
            if ($comprobante !== '' && $comprobante !== $subida['nombre']) {
                (new Comprobante())->borrar($comprobante);
            }
            $comprobante = $subida['nombre'];
        }

        $gasto = [
            'empresa_id'    => (int) $empresa['id'],
            'usuario_id'    => $this->dueno($usuario, $empresa, $actual, $input),
            'categoria_id'  => (int) $categoria['id'],
            'concepto'      => $concepto,
            'proveedor'     => $proveedor,
            'fecha'         => $fecha,
            'monto'         => $monto,
            'moneda'        => (string) ($actual['moneda'] ?? ($pais['moneda'] ?? 'USD')),
            'tipo_doc'      => $tipo,
            'id_fiscal'     => mb_substr(trim((string) ($input['id_fiscal'] ?? '')), 0, 40),
            'num_doc'       => mb_substr(trim((string) ($input['num_doc'] ?? '')), 0, 40),
            'codigo_fiscal' => mb_substr(trim((string) ($input['codigo_fiscal'] ?? '')), 0, 80),
            'nota'          => mb_substr(trim((string) ($input['nota'] ?? '')), 0, 500),
            'comprobante'   => $comprobante,
            'estado'        => (string) ($actual['estado'] ?? 'legalizado'),
            'creado'        => (string) ($actual['creado'] ?? date('Y-m-d H:i:s')),
        ];
        $excepto = $actual === null ? null : (int) $actual['id'];
        $otros   = $this->candidatosDe($empresa, $gasto, $excepto);
        $gasto   = (new GastoEvaluador())->evaluar($gasto, $empresa, $categoria, $otros, $excepto);

        $model = model(GastoModel::class);
        if ($actual === null) {
            $id = $model->insert($gasto, true);
        } else {
            $id = (int) $actual['id'];
            unset($gasto['creado']);
            $model->update($id, $gasto);
        }
        $guardado = $model->find($id);
        if (! is_array($guardado)) {
            return ['ok' => false, 'errores' => ['No pudimos guardar el gasto.']];
        }
        $this->reevaluar((int) $empresa['id'], $guardado, $actual);
        $frescos = $model->find($id);

        return is_array($frescos)
            ? ['ok' => true, 'errores' => [], 'gasto' => $frescos]
            : ['ok' => false, 'errores' => ['No pudimos guardar el gasto.']];
    }

    /**
     * @param array<string, mixed>|null $actual
     *
     * @return array<string, mixed>|null
     */
    private function categoriaValida(int $categoriaId, int $empresaId, ?array $actual): ?array
    {
        $categoria = model(CategoriaModel::class)->find($categoriaId);
        if (! is_array($categoria) || (int) $categoria['empresa_id'] !== $empresaId) {
            return null;
        }
        if ((int) $categoria['activa'] !== 1 && ($actual === null || (int) $actual['categoria_id'] !== $categoriaId)) {
            return null;
        }

        return $categoria;
    }

    /**
     * @param array<string, mixed>      $usuario
     * @param array<string, mixed>      $empresa
     * @param array<string, mixed>|null $actual
     * @param array<string, mixed>      $input
     */
    private function dueno(array $usuario, array $empresa, ?array $actual, array $input): int
    {
        if ($actual !== null) {
            return (int) $actual['usuario_id'];
        }
        $dueno = (int) $usuario['id'];
        if ($usuario['rol'] !== 'administrador') {
            return $dueno;
        }
        $candidato = (int) ($input['usuario_id'] ?? 0);
        if ($candidato < 1) {
            return $dueno;
        }
        $miembro = model(UsuarioModel::class)
            ->where('empresa_id', $empresa['id'])
            ->where('activo', 1)
            ->find($candidato);

        return is_array($miembro) ? $candidato : $dueno;
    }

    private function cupo(int $empresaId): int
    {
        $empresa = model(\App\Models\EmpresaModel::class)->find($empresaId);

        return model(GastoModel::class)
            ->where('empresa_id', $empresaId)
            ->where('creado >=', ZonaHoraria::inicioMesCupo(is_array($empresa) ? $empresa : null))
            ->countAllResults();
    }

    /**
     * Exposed for tests: gastos que cuentan para el cupo del mes en la TZ de la empresa.
     *
     * @param array<string, mixed> $empresa
     */
    public function cupoUsado(array $empresa): int
    {
        return $this->cupo((int) $empresa['id']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function indiceCategorias(int $empresaId): array
    {
        $indice = [];
        foreach (model(CategoriaModel::class)->where('empresa_id', $empresaId)->findAll() as $categoria) {
            $indice[(int) $categoria['id']] = $categoria;
        }

        return $indice;
    }

    /**
     * @param array<string, mixed>                $empresa
     * @param array<string, mixed>|null           $ancla
     * @param array<string, mixed>|null           $previo
     *
     * @return list<array<string, mixed>>
     */
    private function gastosAfectados(int $empresaId, array $empresa, ?array $ancla, ?array $previo): array
    {
        $porId = [];
        if ($ancla !== null && isset($ancla['id'])) {
            $vivo = model(GastoModel::class)->find((int) $ancla['id']);
            if (is_array($vivo) && (int) $vivo['empresa_id'] === $empresaId) {
                $porId[(int) $vivo['id']] = $vivo;
            }
        }

        $semillas = array_values(array_filter([$ancla, $previo], static fn ($g) => is_array($g)));
        foreach ($this->candidatosDeVarios($empresaId, $empresa, $semillas) as $candidato) {
            $porId[(int) $candidato['id']] = $candidato;
        }

        return array_values($porId);
    }

    /**
     * Peers needed so duplicate flags stay correct when a neighbor also matches a third expense.
     *
     * @param array<string, mixed>         $empresa
     * @param list<array<string, mixed>>   $afectados
     *
     * @return list<array<string, mixed>>
     */
    private function universoDuplicados(int $empresaId, array $empresa, array $afectados): array
    {
        $porId = [];
        foreach ($afectados as $gasto) {
            $porId[(int) $gasto['id']] = $gasto;
        }
        foreach ($this->candidatosDeVarios($empresaId, $empresa, $afectados) as $candidato) {
            $porId[(int) $candidato['id']] = $candidato;
        }

        return array_values($porId);
    }

    /**
     * @param array<string, mixed> $empresa
     * @param array<string, mixed> $gasto
     *
     * @return list<array<string, mixed>>
     */
    private function candidatosDe(array $empresa, array $gasto, ?int $exceptoId = null): array
    {
        return $this->candidatosDeVarios((int) $empresa['id'], $empresa, [$gasto], $exceptoId);
    }

    /**
     * @param array<string, mixed>       $empresa
     * @param list<array<string, mixed>> $gastos
     *
     * @return list<array<string, mixed>>
     */
    private function candidatosDeVarios(int $empresaId, array $empresa, array $gastos, ?int $exceptoId = null): array
    {
        if ((int) ($empresa['detectar_duplicados'] ?? 1) !== 1 || $gastos === []) {
            return [];
        }

        $dias      = (int) ($empresa['dias_duplicado'] ?? 3);
        $evaluador = new GastoEvaluador();
        $brutos    = $this->buscarCandidatosBrutos($empresaId, $gastos, $dias, $exceptoId);
        $porId     = [];

        foreach ($gastos as $gasto) {
            foreach ($brutos as $candidato) {
                $id = (int) $candidato['id'];
                if ($exceptoId !== null && $id === $exceptoId) {
                    continue;
                }
                if (isset($gasto['id']) && $id === (int) $gasto['id']) {
                    continue;
                }
                if ($evaluador->esDuplicadoDe($gasto, $candidato, $dias)) {
                    $porId[$id] = $candidato;
                }
            }
        }

        return array_values($porId);
    }

    /**
     * Wide DB fetch; precise duplicate matching happens in PHP via GastoEvaluador.
     *
     * @param list<array<string, mixed>> $gastos
     *
     * @return list<array<string, mixed>>
     */
    private function buscarCandidatosBrutos(int $empresaId, array $gastos, int $dias, ?int $exceptoId): array
    {
        $porId = [];

        foreach ($gastos as $gasto) {
            $numDoc = trim((string) ($gasto['num_doc'] ?? ''));
            if ($numDoc !== '') {
                $consulta = db_connect()->table('gastos')
                    ->where('empresa_id', $empresaId)
                    ->where('num_doc !=', '');
                if ($exceptoId !== null) {
                    $consulta->where('id !=', $exceptoId);
                }
                if (isset($gasto['id'])) {
                    $consulta->where('id !=', (int) $gasto['id']);
                }
                foreach ($consulta->get()->getResultArray() as $fila) {
                    if (Montos::texto((string) $fila['num_doc']) === Montos::texto($numDoc)
                        && Montos::texto((string) $fila['id_fiscal']) === Montos::texto((string) ($gasto['id_fiscal'] ?? ''))) {
                        $porId[(int) $fila['id']] = $fila;
                    }
                }
            }

            $fechaTs = strtotime((string) ($gasto['fecha'] ?? '')) ?: 0;
            if ($fechaTs === 0) {
                continue;
            }
            $desde = date('Y-m-d', $fechaTs - ($dias * 86400));
            $hasta = date('Y-m-d', $fechaTs + ($dias * 86400));
            $monto = (float) ($gasto['monto'] ?? 0);

            $consulta = db_connect()->table('gastos')
                ->where('empresa_id', $empresaId)
                ->where('fecha >=', $desde)
                ->where('fecha <=', $hasta)
                ->where('monto >=', $monto - 0.001)
                ->where('monto <=', $monto + 0.001);
            if ($exceptoId !== null) {
                $consulta->where('id !=', $exceptoId);
            }
            if (isset($gasto['id'])) {
                $consulta->where('id !=', (int) $gasto['id']);
            }
            foreach ($consulta->get()->getResultArray() as $fila) {
                if (Montos::texto((string) $fila['proveedor']) === Montos::texto((string) ($gasto['proveedor'] ?? ''))) {
                    $porId[(int) $fila['id']] = $fila;
                }
            }
        }

        return array_values($porId);
    }

    /**
     * @param array<string, mixed> $actual
     * @param array<string, mixed> $campos
     */
    private function flagsDistintos(array $actual, array $campos): bool
    {
        foreach (['estado', 'duplicado', 'fuera_politica', 'sin_comprobante', 'fiscal_incompleto'] as $campo) {
            if ((string) ($actual[$campo] ?? '') !== (string) $campos[$campo]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array<string, mixed>> $cambios
     */
    private function aplicarCambios(array $cambios): int
    {
        if ($cambios === []) {
            $this->ultimasEscrituras = [];

            return 0;
        }

        $model = model(GastoModel::class);
        $model->updateBatch($cambios, 'id');
        $this->ultimasEscrituras = array_map(static fn (array $fila): int => (int) $fila['id'], $cambios);

        return count($cambios);
    }
}
