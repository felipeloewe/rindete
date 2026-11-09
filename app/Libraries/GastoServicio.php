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
        $this->reevaluar((int) $gasto['empresa_id']);

        return ['ok' => true, 'errores' => []];
    }

    public function reevaluar(int $empresaId): void
    {
        $empresa = model(\App\Models\EmpresaModel::class)->find($empresaId);
        if (! is_array($empresa)) {
            return;
        }
        $gastos     = model(GastoModel::class)->where('empresa_id', $empresaId)->findAll();
        $categorias = $this->indiceCategorias($empresaId);
        $evaluador  = new GastoEvaluador();
        foreach ($gastos as $gasto) {
            $categoria = $categorias[(int) $gasto['categoria_id']] ?? null;
            $nuevo     = $evaluador->evaluar($gasto, $empresa, $categoria, $gastos, (int) $gasto['id']);
            model(GastoModel::class)->update($gasto['id'], [
                'estado'            => $nuevo['estado'],
                'duplicado'         => $nuevo['duplicado'],
                'fuera_politica'    => $nuevo['fuera_politica'],
                'sin_comprobante'   => $nuevo['sin_comprobante'],
                'fiscal_incompleto' => $nuevo['fiscal_incompleto'],
            ]);
        }
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
        $otros = model(GastoModel::class)->where('empresa_id', $empresa['id'])->findAll();
        $gasto = (new GastoEvaluador())->evaluar($gasto, $empresa, $categoria, $otros, $actual === null ? null : (int) $actual['id']);

        $model = model(GastoModel::class);
        if ($actual === null) {
            $id = $model->insert($gasto, true);
        } else {
            $id = (int) $actual['id'];
            unset($gasto['creado']);
            $model->update($id, $gasto);
        }
        $this->reevaluar((int) $empresa['id']);
        $guardado = $model->find($id);

        return is_array($guardado)
            ? ['ok' => true, 'errores' => [], 'gasto' => $guardado]
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
        return model(GastoModel::class)
            ->where('empresa_id', $empresaId)
            ->where('creado >=', date('Y-m-01 00:00:00'))
            ->countAllResults();
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
}
