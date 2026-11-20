<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\CategoriaModel;
use App\Models\FondoModel;
use App\Models\GastoModel;
use App\Models\RendicionModel;
use App\Models\UsuarioModel;

/**
 * Estados de cuenta del período. El saldo es lo entregado menos lo rendido.
 * Un gasto entra como rendido cuando quedó legalizado, en rendición o aprobado.
 */
class InformeServicio
{
    /** @var list<string> */
    public const RENDIDOS = ['legalizado', 'en_rendicion', 'aprobado'];

    /**
     * @return array{0: string, 1: string}
     */
    public static function limites(int $anio, int $mes): array
    {
        if ($mes < 1 || $mes > 12) {
            return [sprintf('%04d-01-01', $anio), sprintf('%04d-01-01', $anio + 1)];
        }
        $desde = sprintf('%04d-%02d-01', $anio, $mes);
        $hasta = date('Y-m-d', strtotime($desde . ' +1 month'));

        return [$desde, $hasta];
    }

    /**
     * @return array{filas: list<array<string, mixed>>, por_usuario: list<array<string, mixed>>}
     */
    public function rendiciones(int $empresaId, int $anio, int $mes, ?int $usuarioId): array
    {
        [$desde, $hasta] = self::limites($anio, $mes);
        $consulta = model(RendicionModel::class)
            ->where('empresa_id', $empresaId)
            ->where('creada >=', $desde . ' 00:00:00')
            ->where('creada <', $hasta . ' 00:00:00');
        if ($usuarioId !== null) {
            $consulta->where('usuario_id', $usuarioId);
        }
        $nombres = $this->nombres($empresaId);
        $filas = [];
        $grupos = [];
        foreach ($consulta->orderBy('creada', 'ASC')->findAll() as $item) {
            $gastos = model(GastoModel::class)->where('rendicion_id', $item['id'])->findAll();
            $monto = 0.0;
            foreach ($gastos as $gasto) {
                $monto += (float) $gasto['monto'];
            }
            $persona = (int) $item['usuario_id'];
            $filas[] = [
                'id'         => (int) $item['id'],
                'usuario_id' => $persona,
                'nombre'     => $nombres[$persona] ?? '',
                'titulo'     => (string) $item['titulo'],
                'estado'     => (string) $item['estado'],
                'creada'     => (string) $item['creada'],
                'cantidad'   => count($gastos),
                'monto'      => $monto,
            ];
            if (! isset($grupos[$persona])) {
                $grupos[$persona] = ['usuario_id' => $persona, 'nombre' => $nombres[$persona] ?? '', 'cantidad' => 0, 'monto' => 0.0];
            }
            $grupos[$persona]['cantidad']++;
            $grupos[$persona]['monto'] += $monto;
        }

        return ['filas' => $filas, 'por_usuario' => array_values($grupos)];
    }

    /**
     * @return array{movimientos: list<array<string, mixed>>, entregado: float, rendido: float, saldo: float, por_rendir: float, por_rendir_abiertas: float}
     */
    public function cartola(int $empresaId, int $anio, int $mes, ?int $usuarioId): array
    {
        [$desde, $hasta] = self::limites($anio, $mes);
        $nombres = $this->nombres($empresaId);
        $movimientos = [];
        $fondos = model(FondoModel::class)
            ->where('empresa_id', $empresaId)
            ->where('creado >=', $desde . ' 00:00:00')
            ->where('creado <', $hasta . ' 00:00:00');
        if ($usuarioId !== null) {
            $fondos->where('usuario_id', $usuarioId);
        }
        foreach ($fondos->findAll() as $fondo) {
            if (in_array((string) ($fondo['origen'] ?? ''), ['tarjeta', 'caja'], true)) {
                continue;
            }
            $persona = (int) $fondo['usuario_id'];
            $movimientos[] = [
                'fecha'   => substr((string) $fondo['creado'], 0, 10),
                'orden'   => 0,
                'nombre'  => $nombres[$persona] ?? '',
                'detalle' => 'Fondo ' . $fondo['motivo'],
                'entrada' => (float) $fondo['monto'],
                'salida'  => 0.0,
            ];
        }
        foreach ($this->gastosRendidos($empresaId, $desde, $hasta, $usuarioId) as $gasto) {
            $persona = (int) $gasto['usuario_id'];
            $movimientos[] = [
                'fecha'   => (string) $gasto['fecha'],
                'orden'   => 1,
                'nombre'  => $nombres[$persona] ?? '',
                'detalle' => (string) $gasto['concepto'],
                'entrada' => 0.0,
                'salida'  => (float) $gasto['monto'],
            ];
        }
        usort($movimientos, static function (array $a, array $b): int {
            return [$a['fecha'], $a['orden'], $a['detalle']] <=> [$b['fecha'], $b['orden'], $b['detalle']];
        });
        $entregado = 0.0;
        $rendido = 0.0;
        $saldo = 0.0;
        foreach ($movimientos as &$movimiento) {
            $saldo += $movimiento['entrada'] - $movimiento['salida'];
            $movimiento['saldo'] = $saldo;
            $entregado += $movimiento['entrada'];
            $rendido += $movimiento['salida'];
        }
        unset($movimiento);

        $abiertos = model(FondoModel::class)->where('empresa_id', $empresaId)->where('estado', 'abierto');
        if ($usuarioId !== null) {
            $abiertos->where('usuario_id', $usuarioId);
        }
        $porRendir = 0.0;
        foreach ($abiertos->findAll() as $fondo) {
            if (in_array((string) ($fondo['origen'] ?? ''), ['tarjeta', 'caja'], true)) {
                continue;
            }
            $porRendir += (float) $fondo['saldo'];
        }

        return [
            'movimientos' => $movimientos,
            'entregado'   => $entregado,
            'rendido'     => $rendido,
            'saldo'               => $saldo,
            'por_rendir'          => $porRendir,
            'por_rendir_abiertas' => (new SaldoRendicion())->porRendirAbierto($empresaId, $usuarioId),
        ];
    }

    /**
     * @return array{filas: list<array<string, mixed>>, por_usuario: list<array<string, mixed>>, por_cuenta: list<array<string, mixed>>}
     */
    public function gastos(int $empresaId, int $anio, int $mes, ?int $usuarioId): array
    {
        [$desde, $hasta] = self::limites($anio, $mes);
        $nombres = $this->nombres($empresaId);
        $categorias = $this->categorias($empresaId);
        $cuentas = $this->cuentasDeGastos($empresaId, $desde, $hasta, $usuarioId);
        $filas = [];
        $personas = [];
        $porCuenta = [];
        foreach ($this->gastosRendidos($empresaId, $desde, $hasta, $usuarioId) as $gasto) {
            $persona = (int) $gasto['usuario_id'];
            $cuenta = $cuentas[(int) $gasto['id']] ?? 'Sin cuenta';
            $filas[] = [
                'fecha'     => (string) $gasto['fecha'],
                'nombre'    => $nombres[$persona] ?? '',
                'concepto'  => (string) $gasto['concepto'],
                'categoria' => $categorias[(int) $gasto['categoria_id']] ?? '',
                'cuenta'    => $cuenta,
                'monto'     => (float) $gasto['monto'],
                'estado'    => (string) $gasto['estado'],
            ];
            if (! isset($personas[$persona])) {
                $personas[$persona] = ['nombre' => $nombres[$persona] ?? '', 'cantidad' => 0, 'monto' => 0.0];
            }
            $personas[$persona]['cantidad']++;
            $personas[$persona]['monto'] += (float) $gasto['monto'];
            if (! isset($porCuenta[$cuenta])) {
                $porCuenta[$cuenta] = ['cuenta' => $cuenta, 'monto' => 0.0];
            }
            $porCuenta[$cuenta]['monto'] += (float) $gasto['monto'];
        }
        usort($filas, static fn (array $a, array $b): int => [$a['fecha'], $a['nombre']] <=> [$b['fecha'], $b['nombre']]);
        ksort($porCuenta);

        return [
            'filas'       => $filas,
            'por_usuario' => array_values($personas),
            'por_cuenta'  => array_values($porCuenta),
        ];
    }

    /**
     * @return array{filas: list<array<string, mixed>>, por_usuario: list<array<string, mixed>>, por_mes: list<array<string, mixed>>, sin_cuenta: float}
     */
    public function asientos(int $empresaId, int $anio, int $mes, ?int $usuarioId): array
    {
        [$desde, $hasta] = self::limites($anio, $mes);
        $nombres = $this->nombres($empresaId);
        $builder = db_connect()->table('pre_asientos')
            ->select('pre_asientos.monto, gastos.fecha, gastos.concepto, gastos.usuario_id, cuentas.codigo, cuentas.nombre')
            ->join('gastos', 'gastos.id = pre_asientos.gasto_id')
            ->join('cuentas', 'cuentas.id = pre_asientos.cuenta_id')
            ->where('gastos.empresa_id', $empresaId)
            ->where('gastos.fecha >=', $desde)
            ->where('gastos.fecha <', $hasta)
            ->whereIn('gastos.estado', self::RENDIDOS);
        if ($usuarioId !== null) {
            $builder->where('gastos.usuario_id', $usuarioId);
        }
        $filas = [];
        $personas = [];
        $meses = [];
        foreach ($builder->orderBy('gastos.fecha', 'ASC')->get()->getResultArray() as $fila) {
            $persona = (int) $fila['usuario_id'];
            $cuenta = $fila['codigo'] . ' ' . $fila['nombre'];
            $periodo = substr((string) $fila['fecha'], 0, 7);
            $monto = (float) $fila['monto'];
            $filas[] = [
                'fecha'   => (string) $fila['fecha'],
                'mes'     => $periodo,
                'nombre'  => $nombres[$persona] ?? '',
                'cuenta'  => $cuenta,
                'concepto'=> (string) $fila['concepto'],
                'monto'   => $monto,
            ];
            if (! isset($personas[$persona])) {
                $personas[$persona] = ['nombre' => $nombres[$persona] ?? '', 'monto' => 0.0];
            }
            $personas[$persona]['monto'] += $monto;
            if (! isset($meses[$periodo][$cuenta])) {
                $meses[$periodo][$cuenta] = 0.0;
            }
            $meses[$periodo][$cuenta] += $monto;
        }
        $porMes = [];
        foreach ($meses as $periodo => $cuentas) {
            $lineas = [];
            $total = 0.0;
            foreach ($cuentas as $cuenta => $monto) {
                $lineas[] = ['cuenta' => $cuenta, 'monto' => $monto];
                $total += $monto;
            }
            $porMes[] = ['mes' => $periodo, 'lineas' => $lineas, 'total' => $total];
        }
        $sinCuenta = 0.0;
        foreach ($this->gastosRendidos($empresaId, $desde, $hasta, $usuarioId) as $gasto) {
            $tiene = db_connect()->table('pre_asientos')->where('gasto_id', $gasto['id'])->countAllResults();
            if ($tiene === 0) {
                $sinCuenta += (float) $gasto['monto'];
            }
        }

        return [
            'filas'       => $filas,
            'por_usuario' => array_values($personas),
            'por_mes'     => $porMes,
            'sin_cuenta'  => $sinCuenta,
        ];
    }

    /**
     * @return array{filas: list<array<string, mixed>>, total: float}
     */
    public function tipos(int $empresaId, int $anio, int $mes, ?int $usuarioId): array
    {
        [$desde, $hasta] = self::limites($anio, $mes);
        $categorias = $this->categorias($empresaId);
        $grupos = [];
        $total = 0.0;
        foreach ($this->gastosRendidos($empresaId, $desde, $hasta, $usuarioId) as $gasto) {
            $id = (int) $gasto['categoria_id'];
            $nombre = $categorias[$id] ?? 'Sin tipo';
            if (! isset($grupos[$id])) {
                $grupos[$id] = ['categoria' => $nombre, 'cantidad' => 0, 'monto' => 0.0];
            }
            $grupos[$id]['cantidad']++;
            $grupos[$id]['monto'] += (float) $gasto['monto'];
            $total += (float) $gasto['monto'];
        }
        usort($grupos, static fn (array $a, array $b): int => $b['monto'] <=> $a['monto']);

        return ['filas' => array_values($grupos), 'total' => $total];
    }

    /**
     * Rendiciones que el mes no alcanzó a cerrar.
     *
     * @return array{filas: list<array<string, mixed>>, total: float}
     */
    public function pendientes(int $empresaId, int $anio, int $mes, ?int $usuarioId): array
    {
        [, $hasta] = self::limites($anio, $mes);
        $consulta = model(RendicionModel::class)
            ->where('empresa_id', $empresaId)
            ->whereIn('estado', ['abierta', 'cierre_pedido'])
            ->where('creada <', $hasta)
            ->orderBy('id', 'ASC');
        if ($usuarioId !== null) {
            $consulta->where('usuario_id', $usuarioId);
        }
        $nombres = $this->nombres($empresaId);
        $saldos = new SaldoRendicion();
        $filas = [];
        $total = 0.0;
        foreach ($consulta->findAll() as $rendicion) {
            $saldo = $saldos->de($rendicion)['saldo'];
            $filas[] = $rendicion + [
                'nombre' => $nombres[(int) $rendicion['usuario_id']] ?? '',
                'saldo'  => $saldo,
            ];
            if ($saldo > 0) {
                $total += $saldo;
            }
        }

        return ['filas' => $filas, 'total' => $total];
    }

    /**
     * Lo que cada persona traspasa a la liquidación de sueldo en el período.
     *
     * @return array{filas: list<array<string, mixed>>, total: float}
     */
    public function remuneraciones(int $empresaId, int $anio, int $mes, ?int $usuarioId): array
    {
        [$desde, $hasta] = self::limites($anio, $mes);
        $consulta = db_connect()->table('gastos')
            ->select('gastos.usuario_id, gastos.monto, categorias.concepto')
            ->join('categorias', 'categorias.id = gastos.categoria_id')
            ->where('gastos.empresa_id', $empresaId)
            ->where('gastos.rendicion_id >', 0)
            ->whereIn('gastos.estado', self::RENDIDOS)
            ->whereIn('categorias.concepto', ['viatico', 'asignacion', 'bien'])
            ->where('gastos.fecha >=', $desde)
            ->where('gastos.fecha <', $hasta);
        if ($usuarioId !== null) {
            $consulta->where('gastos.usuario_id', $usuarioId);
        }
        $nombres = $this->nombres($empresaId);
        $cuentas = new RendicionDirecta();
        $grupos = [];
        foreach ($consulta->orderBy('gastos.usuario_id', 'ASC')->get()->getResultArray() as $gasto) {
            $id = (int) $gasto['usuario_id'];
            if (! isset($grupos[$id])) {
                $grupos[$id] = [
                    'usuario_id' => $id,
                    'nombre'     => $nombres[$id] ?? '',
                    'viatico'    => 0.0,
                    'asignacion' => 0.0,
                    'bien'       => 0.0,
                    'cuentas'    => [],
                ];
            }
            $concepto = (string) $gasto['concepto'];
            $grupos[$id][$concepto] += (float) $gasto['monto'];
        }
        $filas = [];
        $total = 0.0;
        foreach ($grupos as $grupo) {
            $grupo['total'] = $grupo['viatico'] + $grupo['asignacion'] + $grupo['bien'];
            foreach (['viatico', 'asignacion', 'bien'] as $concepto) {
                if ($grupo[$concepto] <= 0) {
                    continue;
                }
                $cuenta = $cuentas->concepto($empresaId, (int) $grupo['usuario_id'], $concepto);
                $grupo['cuentas'][$concepto] = is_array($cuenta)
                    ? trim((string) $cuenta['codigo'] . ' ' . $cuenta['nombre'])
                    : '';
            }
            $total += $grupo['total'];
            $filas[] = $grupo;
        }

        return ['filas' => $filas, 'total' => $total];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function gastosRendidos(int $empresaId, string $desde, string $hasta, ?int $usuarioId): array
    {
        $consulta = model(GastoModel::class)
            ->where('empresa_id', $empresaId)
            ->where('fecha >=', $desde)
            ->where('fecha <', $hasta)
            ->whereIn('estado', self::RENDIDOS);
        if ($usuarioId !== null) {
            $consulta->where('usuario_id', $usuarioId);
        }

        return $consulta->findAll();
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

    /**
     * @return array<int, string>
     */
    private function categorias(int $empresaId): array
    {
        $nombres = [];
        foreach (model(CategoriaModel::class)->where('empresa_id', $empresaId)->findAll() as $categoria) {
            $nombres[(int) $categoria['id']] = (string) $categoria['nombre'];
        }

        return $nombres;
    }

    /**
     * @return array<int, string>
     */
    private function cuentasDeGastos(int $empresaId, string $desde, string $hasta, ?int $usuarioId): array
    {
        $builder = db_connect()->table('pre_asientos')
            ->select('pre_asientos.gasto_id, cuentas.codigo, cuentas.nombre')
            ->join('gastos', 'gastos.id = pre_asientos.gasto_id')
            ->join('cuentas', 'cuentas.id = pre_asientos.cuenta_id')
            ->where('gastos.empresa_id', $empresaId)
            ->where('gastos.fecha >=', $desde)
            ->where('gastos.fecha <', $hasta);
        if ($usuarioId !== null) {
            $builder->where('gastos.usuario_id', $usuarioId);
        }
        $cuentas = [];
        foreach ($builder->get()->getResultArray() as $fila) {
            $cuentas[(int) $fila['gasto_id']] = $fila['codigo'] . ' ' . $fila['nombre'];
        }

        return $cuentas;
    }
}
