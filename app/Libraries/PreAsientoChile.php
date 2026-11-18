<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\CuentaModel;
use App\Models\UsuarioModel;

/**
 * Comprobante de traspaso chileno. Junta las rendiciones del mes en un preasiento
 * por persona o en uno solo del mes. Nunca arma uno por rendición.
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
        $haber = model(CuentaModel::class)->find((int) ($empresa['preasiento_cuenta_id'] ?? 0));
        if (! is_array($haber) || (int) $haber['empresa_id'] !== (int) $empresa['id'] || (int) $haber['activa'] !== 1) {
            return $this->falla('Elige la cuenta de contrapartida. En Chile es el fondo por rendir o el anticipo al personal.');
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

        $this->reemplazar((int) $empresa['id'], $anio, $mes, $grupos, $haber, $agrupacion, $conAuxiliar, $nombres, $avisos);

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
     * @param array<string, array{usuario_id: int|null, moneda: string, debe: array<int, float>, personas: array<int, float>}> $grupos
     * @param array<string, mixed> $haber
     * @param array<int, string> $nombres
     * @param list<string> $avisos
     */
    private function reemplazar(int $empresaId, int $anio, int $mes, array $grupos, array $haber, string $agrupacion, bool $conAuxiliar, array $nombres, array &$avisos): void
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
        foreach ($grupos as $grupo) {
            $persona = $grupo['usuario_id'];
            $glosa = $persona === null
                ? 'Centralización de rendiciones, ' . $periodo
                : 'Rendiciones de ' . ($nombres[$persona] ?? 'la persona') . ', ' . $periodo;
            $db->table('preasiento_comprobantes')->insert([
                'empresa_id' => $empresaId,
                'anio'       => $anio,
                'mes'        => $mes,
                'agrupacion' => $agrupacion,
                'usuario_id' => $persona,
                'tipo'       => 'traspaso',
                'glosa'      => $glosa,
                'fecha'      => $fecha,
                'moneda'     => $grupo['moneda'],
                'estado'     => 'propuesto',
                'creado'     => date('Y-m-d H:i:s'),
            ]);
            $id = (int) $db->insertID();
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

    }
