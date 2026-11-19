<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\CategoriaModel;
use App\Models\GastoModel;
use App\Models\TarjetaModel;
use App\Models\UsuarioModel;

class CargoTarjeta
{
    /**
     * @param array<string, mixed> $usuario
     *
     * @return array{ok: bool, errores: list<string>, estado?: string}
     */
    public function crear(array $usuario, int $empresaId, int $tarjetaId, int $categoriaId, string $motivo, float $monto, string $moneda, bool $dte): array
    {
        $tarjeta = model(TarjetaModel::class)->find($tarjetaId);
        if (! is_array($tarjeta) || (int) $tarjeta['empresa_id'] !== $empresaId || (int) $tarjeta['activa'] !== 1) {
            return ['ok' => false, 'errores' => ['Elige una tarjeta vigente.']];
        }
        if (! $this->puede($usuario, $tarjetaId)) {
            return ['ok' => false, 'errores' => ['Esta tarjeta la opera quien tiene acceso.']];
        }
        $motivo = mb_substr(trim($motivo), 0, 180);
        if ($motivo === '' || $monto <= 0) {
            return ['ok' => false, 'errores' => ['Escribe el motivo y un monto mayor que cero.']];
        }
        $categoria = model(CategoriaModel::class)->find($categoriaId);
        if (! is_array($categoria) || (int) $categoria['empresa_id'] !== $empresaId) {
            return ['ok' => false, 'errores' => ['Elige una categoría de la empresa.']];
        }
        $propias = db_connect()->table('tarjeta_categorias')->where('tarjeta_id', $tarjetaId)->get()->getResultArray();
        $permitida = false;
        foreach ($propias as $fila) {
            if ((int) $fila['categoria_id'] === $categoriaId) {
                $permitida = true;
            }
        }
        $detalle = '';
        $estado = 'validado';
        if ($propias !== [] && ! $permitida) {
            $estado = 'observado';
            $detalle = 'Ese gasto no es de esta tarjeta.';
        }
        $tope = (float) $tarjeta['monto_aceptado'];
        if ($tope > 0 && $monto > $tope) {
            $estado = 'observado';
            $detalle = 'Supera el monto aceptado de la tarjeta.';
        }
        $fondoId = null;
        $rendicionId = null;
        if ($estado === 'validado') {
            $abierto = (new FondoAbierto())->abrir(
                $empresaId,
                (int) $usuario['id'],
                $categoriaId,
                $motivo,
                $monto,
                $moneda,
                'tarjeta',
                'Tarjeta ' . $motivo,
            );
            if (! $abierto['ok']) {
                return $abierto;
            }
            $fondoId = $abierto['fondo_id'];
            $rendicionId = $abierto['rendicion_id'];
        }
        db_connect()->table('cargos_tarjeta')->insert([
            'empresa_id'   => $empresaId,
            'tarjeta_id'   => $tarjetaId,
            'usuario_id'   => (int) $usuario['id'],
            'categoria_id' => $categoriaId,
            'motivo'       => $motivo,
            'monto'        => $monto,
            'moneda'       => $moneda,
            'dte'          => $dte ? 1 : 0,
            'comprobante'  => '',
            'estado'       => $estado,
            'detalle'      => $detalle,
            'fondo_id'     => $fondoId,
            'rendicion_id' => $rendicionId,
            'creada'       => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'errores' => [], 'estado' => $estado];
    }

    /**
     * @param array<string, mixed> $usuario
     */
    public function puede(array $usuario, int $tarjetaId): bool
    {
        if (($usuario['rol'] ?? '') === 'administrador') {
            return true;
        }
        $fila = db_connect()->table('tarjeta_usuarios')
            ->where('tarjeta_id', $tarjetaId)
            ->where('usuario_id', (int) $usuario['id'])
            ->get()
            ->getRowArray();

        return is_array($fila);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function tarjetasDe(array $usuario, int $empresaId): array
    {
        $todas = model(TarjetaModel::class)->where('empresa_id', $empresaId)->where('activa', 1)->orderBy('nombre', 'ASC')->findAll();
        if (($usuario['rol'] ?? '') === 'administrador') {
            return $todas;
        }

        return array_values(array_filter(
            $todas,
            fn (array $tarjeta): bool => $this->puede($usuario, (int) $tarjeta['id']),
        ));
    }

    /**
     * @return array{filas: list<array<string, mixed>>, total: float}
     */
    public function estado(int $empresaId, int $anio, int $mes, ?int $usuarioId): array
    {
        [$desde, $hasta] = InformeServicio::limites($anio, $mes);
        $nombres = $this->nombres($empresaId);
        $tarjetas = [];
        foreach (model(TarjetaModel::class)->where('empresa_id', $empresaId)->findAll() as $tarjeta) {
            $tarjetas[(int) $tarjeta['id']] = (string) $tarjeta['nombre'];
        }
        $consulta = db_connect()->table('cargos_tarjeta')
            ->where('empresa_id', $empresaId)
            ->where('creada >=', $desde)
            ->where('creada <', $hasta)
            ->orderBy('id', 'ASC');
        if ($usuarioId !== null) {
            $consulta->where('usuario_id', $usuarioId);
        }
        $filas = [];
        $total = 0.0;
        foreach ($consulta->get()->getResultArray() as $cargo) {
            $rendido = $this->rendido((int) ($cargo['rendicion_id'] ?? 0));
            $filas[] = $cargo + [
                'nombre'   => $nombres[(int) $cargo['usuario_id']] ?? '',
                'tarjeta'  => $tarjetas[(int) $cargo['tarjeta_id']] ?? '',
                'facturas' => -1 * (float) $cargo['monto'],
                'rendido'  => $rendido,
            ];
            $total += (float) $cargo['monto'];
        }

        return ['filas' => $filas, 'total' => $total];
    }

    /**
     * @return array{filas: list<array<string, mixed>>, total: float}
     */
    public function desviaciones(int $empresaId, int $anio, int $mes, ?int $usuarioId): array
    {
        $estado = $this->estado($empresaId, $anio, $mes, $usuarioId);
        $filas = [];
        $total = 0.0;
        foreach ($estado['filas'] as $fila) {
            if ((string) $fila['estado'] !== 'validado') {
                continue;
            }
            $diferencia = round((float) $fila['monto'] - (float) $fila['rendido'], 2);
            if (abs($diferencia) < 0.009) {
                continue;
            }
            $fila['diferencia'] = $diferencia;
            $filas[] = $fila;
            $total += $diferencia;
        }

        return ['filas' => $filas, 'total' => $total];
    }

    private function rendido(int $rendicionId): float
    {
        if ($rendicionId < 1) {
            return 0.0;
        }
        $total = 0.0;
        foreach (model(GastoModel::class)->where('rendicion_id', $rendicionId)->findAll() as $gasto) {
            $total += (float) $gasto['monto'];
        }

        return round($total, 2);
    }

    /**
     * @return array<int, string>
     */
    private function nombres(int $empresaId): array
    {
        $nombres = [];
        foreach (model(UsuarioModel::class)->where('empresa_id', $empresaId)->findAll() as $persona) {
            $nombres[(int) $persona['id']] = (string) $persona['nombre'];
        }

        return $nombres;
    }
}
