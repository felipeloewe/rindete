<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\CuentaModel;
use App\Models\UsuarioModel;

/**
 * Preasientos de Chile. Al entregar un fondo sale el dinero y nace el fondo por rendir.
 * Al rendir, ese fondo pasa a la cuenta del gasto. Cada momento queda agrupado por
 * persona o en uno solo del mes. Nunca hay uno por rendición ni por fondo.
 * El auxiliar lleva el RUT del trabajador solo cuando el administrador lo pide.
 */
class PreAsientoChile
{
    /** @var list<string> */
    private const MESES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    /**
     * @param array<string, mixed> $empresa
     *
     * @return array{ok: bool, errores: list<string>, avisos: list<string>}
     */
    public function armar(array $empresa, int $anio, int $mes): array
    {
        if ((string) ($empresa['pais'] ?? '') !== 'CL') {
            return $this->falla('El preasiento normativo está definido para Chile.');
        }
        if ($mes < 1 || $mes > 12) {
            return $this->falla('Elige un mes.');
        }
        $haber = $this->cuentaDe($empresa, (int) ($empresa['preasiento_cuenta_id'] ?? 0));
        if ($haber === null) {
            return $this->falla('Elige la cuenta de contrapartida. En Chile es el fondo por rendir o el anticipo al personal.');
        }
        $banco = $this->cuentaDe($empresa, (int) ($empresa['preasiento_salida_id'] ?? 0));
        if ($banco === null) {
            return $this->falla('Elige la cuenta de donde sale el dinero. Es el banco o la caja.');
        }
        if ((int) $banco['id'] === (int) $haber['id']) {
            return $this->falla('Elige otra cuenta para la salida. El fondo por rendir ya está en la contrapartida.');
        }

        $agrupacion = (string) ($empresa['preasiento_agrupacion'] ?? 'mes') === 'usuario' ? 'usuario' : 'mes';
        $conAuxiliar = (int) ($empresa['preasiento_auxiliar'] ?? 0) === 1;
        [$desde, $hasta] = InformeServicio::limites($anio, $mes);
        $filas = db_connect()->table('gastos')
            ->select('gastos.id, gastos.concepto, gastos.monto, gastos.moneda, gastos.usuario_id, pre_asientos.cuenta_id')
            ->join('pre_asientos', 'pre_asientos.gasto_id = gastos.id', 'left')
            ->where('gastos.empresa_id', (int) $empresa['id'])
            ->where('gastos.fecha >=', $desde)
            ->where('gastos.fecha <', $hasta)
            ->where('gastos.rendicion_id >', 0)
            ->whereIn('gastos.estado', InformeServicio::RENDIDOS)
            ->get()
            ->getResultArray();

        $nombres = $this->nombres((int) $empresa['id']);
        $avisos = [];
        $salidas = $this->gruposFondos((int) $empresa['id'], $desde, $hasta, $agrupacion);
        $grupos = [];
        foreach ($filas as $fila) {
            $cuentaId = (int) ($fila['cuenta_id'] ?? 0);
            if ($cuentaId < 1) {
                $avisos[] = $fila['concepto'] . ' no tiene cuenta y quedó fuera del preasiento.';

                continue;
            }
            $persona = (int) $fila['usuario_id'];
            $moneda = (string) $fila['moneda'];
            $clave = ($agrupacion === 'usuario' ? $persona : 0) . '|' . $moneda;
            if (! isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'usuario_id' => $agrupacion === 'usuario' ? $persona : null,
                    'moneda'     => $moneda,
                    'debe'       => [],
                    'personas'   => [],
                ];
            }
            $grupos[$clave]['debe'][$cuentaId] = ($grupos[$clave]['debe'][$cuentaId] ?? 0) + (float) $fila['monto'];
            $grupos[$clave]['personas'][$persona] = ($grupos[$clave]['personas'][$persona] ?? 0) + (float) $fila['monto'];
        }

        $this->reemplazar((int) $empresa['id'], $anio, $mes, $grupos, $salidas, $haber, $banco, $agrupacion, $conAuxiliar, $nombres, $avisos);

        if ($salidas === []) {
            $avisos[] = 'No hay fondos entregados en este mes.';
        }
        if ($grupos === []) {
            $avisos[] = 'No hay gastos dentro de rendiciones en este mes.';
        }

        return ['ok' => true, 'errores' => [], 'avisos' => $avisos];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function leer(int $empresaId, int $anio, int $mes): array
    {
        $comprobantes = db_connect()->table('preasiento_comprobantes')
            ->where('empresa_id', $empresaId)
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
        foreach ($comprobantes as &$comprobante) {
            $lineas = db_connect()->table('preasiento_lineas')
                ->select('preasiento_lineas.*, cuentas.codigo, cuentas.nombre')
                ->join('cuentas', 'cuentas.id = preasiento_lineas.cuenta_id')
                ->where('comprobante_id', $comprobante['id'])
                ->orderBy('orden', 'ASC')
                ->get()
                ->getResultArray();
            $debe = 0.0;
            $haber = 0.0;
            foreach ($lineas as $linea) {
                $debe += (float) $linea['debe'];
                $haber += (float) $linea['haber'];
            }
            $comprobante['lineas'] = $lineas;
            $comprobante['debe'] = round($debe, 2);
            $comprobante['haber'] = round($haber, 2);
        }
        unset($comprobante);

        return $comprobantes;
    }

    /**
     * @return array{cuentas: list<array<string, mixed>>}
     */
    public function mayor(int $empresaId, int $anio, int $mes): array
    {
        $cuentas = [];
        foreach ($this->leer($empresaId, $anio, $mes) as $comprobante) {
            foreach ($comprobante['lineas'] as $linea) {
                $id = (int) $linea['cuenta_id'];
                if (! isset($cuentas[$id])) {
                    $cuentas[$id] = [
                        'codigo'       => (string) $linea['codigo'],
                        'nombre'       => (string) $linea['nombre'],
                        'debe'         => 0.0,
                        'haber'        => 0.0,
                        'movimientos'  => [],
                    ];
                }
                $debe = (float) $linea['debe'];
                $haberLinea = (float) $linea['haber'];
                $cuentas[$id]['debe'] += $debe;
                $cuentas[$id]['haber'] += $haberLinea;
                $cuentas[$id]['movimientos'][] = [
                    'fecha'    => (string) $comprobante['fecha'],
                    'glosa'    => (string) $linea['glosa'],
                    'auxiliar' => (string) $linea['auxiliar'],
                    'debe'     => $debe,
                    'haber'    => $haberLinea,
                ];
            }
        }
        foreach ($cuentas as &$cuenta) {
            $cuenta['debe'] = round($cuenta['debe'], 2);
            $cuenta['haber'] = round($cuenta['haber'], 2);
            $cuenta['saldo'] = round($cuenta['debe'] - $cuenta['haber'], 2);
        }
        unset($cuenta);
        uasort($cuentas, static fn (array $a, array $b): int => strcmp((string) $a['codigo'], (string) $b['codigo']));

        return ['cuentas' => array_values($cuentas)];
    }

    /**
     * @param array<string, array{usuario_id: int|null, moneda: string, debe: array<int, float>, personas: array<int, float>}> $grupos
     * @param array<string, array{usuario_id: int|null, moneda: string, personas: array<int, float>}> $salidas
     * @param array<string, mixed> $haber
     * @param array<string, mixed> $banco
     * @param array<int, string> $nombres
     * @param list<string> $avisos
     */
    private function reemplazar(int $empresaId, int $anio, int $mes, array $grupos, array $salidas, array $haber, array $banco, string $agrupacion, bool $conAuxiliar, array $nombres, array &$avisos): void
    {
        $db = db_connect();
        $viejos = $db->table('preasiento_comprobantes')
            ->select('id')
            ->where('empresa_id', $empresaId)
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->where('estado', 'propuesto')
            ->get()
            ->getResultArray();
        $db->transStart();
        if ($viejos !== []) {
            $ids = array_map(static fn (array $fila): int => (int) $fila['id'], $viejos);
            $db->table('preasiento_lineas')->whereIn('comprobante_id', $ids)->delete();
            $db->table('preasiento_comprobantes')->whereIn('id', $ids)->delete();
        }
        $periodo = self::MESES[$mes] . ' ' . $anio;
        $fecha = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $anio, $mes)));
        $sinRut = [];
        foreach ($salidas as $salida) {
            $persona = $salida['usuario_id'];
            $glosa = $persona === null
                ? 'Salida de fondos, ' . $periodo
                : 'Salida de fondos de ' . ($nombres[$persona] ?? 'la persona') . ', ' . $periodo;
            $id = $this->comprobante($db, $empresaId, $anio, $mes, $agrupacion, $persona, 'salida', $glosa, $fecha, $salida['moneda']);
            $orden = 1;
            $total = 0.0;
            if ($conAuxiliar && $persona === null) {
                foreach ($salida['personas'] as $usuarioId => $monto) {
                    $monto = round($monto, 2);
                    $total += $monto;
                    $this->linea($db, $id, $orden, (int) $haber['id'], 'Fondos por rendir, ' . ($nombres[$usuarioId] ?? ''), $monto, 0, $this->rut($usuarioId, $nombres, $avisos, $sinRut));
                    $orden++;
                }
            } else {
                foreach ($salida['personas'] as $monto) {
                    $total += round($monto, 2);
                }
                $total = round($total, 2);
                $rut = $conAuxiliar && $persona !== null ? $this->rut($persona, $nombres, $avisos, $sinRut) : '';
                $this->linea($db, $id, $orden, (int) $haber['id'], 'Fondos por rendir' . ($persona !== null ? ', ' . ($nombres[$persona] ?? '') : ''), $total, 0, $rut);
                $orden++;
            }
            $this->linea($db, $id, $orden, (int) $banco['id'], 'Salida de dinero', 0, round($total, 2), '');
        }
        foreach ($grupos as $grupo) {
            $persona = $grupo['usuario_id'];
            $glosa = $persona === null
                ? 'Centralización de rendiciones, ' . $periodo
                : 'Rendiciones de ' . ($nombres[$persona] ?? 'la persona') . ', ' . $periodo;
            $id = $this->comprobante($db, $empresaId, $anio, $mes, $agrupacion, $persona, 'traspaso', $glosa, $fecha, $grupo['moneda']);
            $orden = 1;
            $total = 0.0;
            ksort($grupo['debe']);
            foreach ($grupo['debe'] as $cuentaId => $monto) {
                $monto = round($monto, 2);
                $total += $monto;
                $rut = $conAuxiliar && $persona !== null ? $this->rut($persona, $nombres, $avisos, $sinRut) : '';
                $db->table('preasiento_lineas')->insert([
                    'comprobante_id' => $id,
                    'orden'          => $orden,
                    'cuenta_id'      => $cuentaId,
                    'glosa'          => 'Rendiciones ' . $periodo,
                    'debe'           => $monto,
                    'haber'          => 0,
                    'auxiliar'       => $rut,
                ]);
                $orden++;
            }
            $total = round($total, 2);
            if ($conAuxiliar && $persona === null) {
                foreach ($grupo['personas'] as $usuarioId => $monto) {
                    $db->table('preasiento_lineas')->insert([
                        'comprobante_id' => $id,
                        'orden'          => $orden,
                        'cuenta_id'      => (int) $haber['id'],
                        'glosa'          => 'Fondos por rendir, ' . ($nombres[$usuarioId] ?? ''),
                        'debe'           => 0,
                        'haber'          => round($monto, 2),
                        'auxiliar'       => $this->rut($usuarioId, $nombres, $avisos, $sinRut),
                    ]);
                    $orden++;
                }
            } else {
                $db->table('preasiento_lineas')->insert([
                    'comprobante_id' => $id,
                    'orden'          => $orden,
                    'cuenta_id'      => (int) $haber['id'],
                    'glosa'          => 'Fondos por rendir' . ($persona !== null ? ', ' . ($nombres[$persona] ?? '') : ''),
                    'debe'           => 0,
                    'haber'          => $total,
                    'auxiliar'       => $conAuxiliar && $persona !== null ? $this->rut($persona, $nombres, $avisos, $sinRut) : '',
                ]);
            }
        }
        $db->transComplete();
    }

    /**
     * @param array<int, string> $nombres
     * @param list<string> $avisos
     * @param array<int, true> $sinRut
     */
    private function rut(int $usuarioId, array $nombres, array &$avisos, array &$sinRut): string
    {
        $persona = model(UsuarioModel::class)->find($usuarioId);
        $rut = is_array($persona) ? trim((string) ($persona['id_fiscal'] ?? '')) : '';
        if ($rut === '' && ! isset($sinRut[$usuarioId])) {
            $sinRut[$usuarioId] = true;
            $avisos[] = 'Falta el RUT de ' . ($nombres[$usuarioId] ?? 'una persona') . '.';
        }

        return $rut;
    }

    /**
     * @return array{ok: false, errores: list<string>, avisos: list<string>}
     */
    private function falla(string $mensaje): array
    {
        return ['ok' => false, 'errores' => [$mensaje], 'avisos' => []];
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
     * @param array<string, mixed> $empresa
     *
     * @return array<string, mixed>|null
     */
    private function cuentaDe(array $empresa, int $id): ?array
    {
        $cuenta = model(CuentaModel::class)->find($id);
        if (! is_array($cuenta) || (int) $cuenta['empresa_id'] !== (int) $empresa['id'] || (int) $cuenta['activa'] !== 1) {
            return null;
        }

        return $cuenta;
    }

    /**
     * @return array<string, array{usuario_id: int|null, moneda: string, personas: array<int, float>}>
     */
    private function gruposFondos(int $empresaId, string $desde, string $hasta, string $agrupacion): array
    {
        $filas = db_connect()->table('fondos')
            ->where('empresa_id', $empresaId)
            ->where('creado >=', $desde . ' 00:00:00')
            ->where('creado <', $hasta . ' 00:00:00')
            ->get()
            ->getResultArray();
        $grupos = [];
        foreach ($filas as $fila) {
            $persona = (int) $fila['usuario_id'];
            $moneda = (string) $fila['moneda'];
            $clave = ($agrupacion === 'usuario' ? $persona : 0) . '|' . $moneda;
            if (! isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'usuario_id' => $agrupacion === 'usuario' ? $persona : null,
                    'moneda'     => $moneda,
                    'personas'   => [],
                ];
            }
            $grupos[$clave]['personas'][$persona] = ($grupos[$clave]['personas'][$persona] ?? 0) + (float) $fila['monto'];
        }

        return $grupos;
    }

    /**
     * @param \CodeIgniter\Database\BaseConnection $db
     */
    private function comprobante($db, int $empresaId, int $anio, int $mes, string $agrupacion, ?int $persona, string $tipo, string $glosa, string $fecha, string $moneda): int
    {
        $db->table('preasiento_comprobantes')->insert([
            'empresa_id' => $empresaId,
            'anio'       => $anio,
            'mes'        => $mes,
            'agrupacion' => $agrupacion,
            'usuario_id' => $persona,
            'tipo'       => $tipo,
            'glosa'      => $glosa,
            'fecha'      => $fecha,
            'moneda'     => $moneda,
            'estado'     => 'propuesto',
            'creado'     => date('Y-m-d H:i:s'),
        ]);

        return (int) $db->insertID();
    }

    /**
     * @param \CodeIgniter\Database\BaseConnection $db
     */
    private function linea($db, int $comprobanteId, int $orden, int $cuentaId, string $glosa, float $debe, float $haber, string $auxiliar): void
    {
        $db->table('preasiento_lineas')->insert([
            'comprobante_id' => $comprobanteId,
            'orden'          => $orden,
            'cuenta_id'      => $cuentaId,
            'glosa'          => $glosa,
            'debe'           => $debe,
            'haber'          => $haber,
            'auxiliar'       => $auxiliar,
        ]);
    }
}
