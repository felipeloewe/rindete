<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Las mismas filas que muestra cada informe, listas para Excel y PDF.
 */
class HojaInforme
{
    /**
     * @param array<string, mixed> $datos
     *
     * @return list<array{titulo: string, headers: list<string>, filas: list<list<string>>}>
     */
    public static function tablas(string $pieza, array $datos, string $moneda): array
    {
        $dinero = static fn (mixed $valor, ?string $propia = null): string => Montos::formato((float) $valor, $propia ?? $moneda);
        $filas = is_array($datos['filas'] ?? null) ? $datos['filas'] : [];

        return match ($pieza) {
            'rendiciones' => [
                self::grupo('Por persona', ['Persona', 'Rendiciones', 'Total'], $datos['por_usuario'] ?? [], static fn (array $fila): array => [
                    (string) $fila['nombre'], (string) $fila['cantidad'], $dinero($fila['monto']),
                ]),
                self::detalle('Rendiciones', ['Rendición', 'Persona', 'Gastos', 'Total', 'Estado'], $filas, static fn (array $fila): array => [
                    (string) $fila['titulo'], (string) $fila['nombre'], (string) $fila['cantidad'], $dinero($fila['monto']), Estados::rendicion((string) $fila['estado']),
                ]),
            ],
            'cartola' => [
                [
                    'titulo' => 'Saldos',
                    'headers' => ['Entregado', 'Rendido', 'Saldo del período', 'Por rendir, fondos abiertos', 'Por rendir, rendiciones abiertas'],
                    'filas' => [[
                        $dinero($datos['entregado'] ?? 0),
                        $dinero($datos['rendido'] ?? 0),
                        $dinero($datos['saldo'] ?? 0),
                        $dinero($datos['por_rendir'] ?? 0),
                        $dinero($datos['por_rendir_abiertas'] ?? 0),
                    ]],
                ],
                self::detalle('Movimientos', ['Fecha', 'Persona', 'Detalle', 'Entra', 'Sale', 'Saldo'], $datos['movimientos'] ?? [], static fn (array $fila): array => [
                    (string) $fila['fecha'], (string) $fila['nombre'], (string) $fila['detalle'],
                    (float) $fila['entrada'] > 0 ? $dinero($fila['entrada']) : '',
                    (float) $fila['salida'] > 0 ? $dinero($fila['salida']) : '',
                    $dinero($fila['saldo'] ?? 0),
                ]),
            ],
            'gastos' => [
                self::grupo('Por persona', ['Persona', 'Gastos', 'Total'], $datos['por_usuario'] ?? [], static fn (array $fila): array => [
                    (string) $fila['nombre'], (string) $fila['cantidad'], $dinero($fila['monto']),
                ]),
                self::grupo('Por cuenta', ['Cuenta', 'Total'], $datos['por_cuenta'] ?? [], static fn (array $fila): array => [
                    (string) $fila['cuenta'], $dinero($fila['monto']),
                ]),
                self::detalle('Gastos', ['Gasto', 'Persona', 'Fecha', 'Tipo', 'Cuenta', 'Monto'], $filas, static fn (array $fila): array => [
                    (string) $fila['concepto'], (string) $fila['nombre'], (string) $fila['fecha'], (string) $fila['categoria'], (string) $fila['cuenta'], $dinero($fila['monto']),
                ]),
            ],
            'asientos' => self::asientos($datos, $dinero),
            'tipos' => [
                self::detalle('Tipo de gasto', ['Tipo de gasto', 'Cantidad', 'Total'], $filas, static fn (array $fila): array => [
                    (string) $fila['categoria'], (string) $fila['cantidad'], $dinero($fila['monto']),
                ]),
            ],
            'pendientes' => [
                self::detalle('Pendientes', ['Rendición', 'Persona', 'Estado', 'Saldo'], $filas, static fn (array $fila): array => [
                    (string) $fila['titulo'], (string) $fila['nombre'], Estados::rendicion((string) $fila['estado']), $dinero($fila['saldo']),
                ]),
            ],
            'remuneraciones' => [
                self::detalle('Remuneraciones', ['Persona', 'Viático', 'Asignación', 'Bien', 'A liquidación'], $filas, static fn (array $fila): array => [
                    (string) $fila['nombre'], $dinero($fila['viatico']), $dinero($fila['asignacion']), $dinero($fila['bien']), $dinero($fila['total']),
                ]),
            ],
            'tarjeta' => [
                self::detalle('Tarjeta', ['Persona', 'Tarjeta', 'Cargo', 'Facturas por pagar', 'Validación', 'Rendido'], $filas, static fn (array $fila): array => [
                    (string) $fila['nombre'], (string) $fila['tarjeta'], (string) $fila['motivo'],
                    $dinero($fila['facturas'], (string) ($fila['moneda'] ?? $moneda)),
                    (string) $fila['estado'] === 'validado' ? 'Validado' : 'Observado',
                    $dinero($fila['rendido'], (string) ($fila['moneda'] ?? $moneda)),
                ]),
            ],
            'desviaciones' => [
                self::detalle('Desviaciones', ['Persona', 'Cargo', 'Pagado', 'Rendido', 'Diferencia'], $filas, static fn (array $fila): array => [
                    (string) $fila['nombre'], (string) $fila['motivo'],
                    $dinero($fila['monto'], (string) ($fila['moneda'] ?? $moneda)),
                    $dinero($fila['rendido'], (string) ($fila['moneda'] ?? $moneda)),
                    $dinero($fila['diferencia'], (string) ($fila['moneda'] ?? $moneda)),
                ]),
            ],
            'caja' => [
                [
                    'titulo' => 'Saldos',
                    'headers' => ['Entregado', 'Reposición', 'Saldo del período'],
                    'filas' => [[$dinero($datos['entregado'] ?? 0), $dinero($datos['reponer'] ?? 0), $dinero($datos['saldo'] ?? 0)]],
                ],
                self::detalle('Movimientos', ['Movimiento', 'Persona', 'Monto', 'Revisión'], $filas, static fn (array $fila): array => [
                    ((string) $fila['tipo'] === 'reposicion' ? 'Reposición' : 'Entrega') . ' · ' . $fila['motivo'],
                    (string) $fila['nombre'],
                    $dinero($fila['monto'], (string) ($fila['moneda'] ?? $moneda)),
                    (int) ($fila['duplicado'] ?? 0) === 1 ? 'Marcado para revisión de caja' : '',
                ]),
            ],
            'socios' => self::socios($filas, $dinero),
            'recuperar' => [
                self::detalle('Por recuperar', ['Entidad', 'Documentos', 'Por recuperar'], $filas, static fn (array $fila): array => [
                    (string) $fila['entidad'], (string) count($fila['lineas'] ?? []), $dinero($fila['monto']),
                ]),
            ],
            'medios' => [
                self::detalle('Movimientos', ['Persona', 'Tarjeta', 'Movimiento', 'Sentido', 'Monto', 'Estado'], $filas, static function (array $fila) use ($dinero, $moneda): array {
                    $estados = ['trazado' => 'Trazado', 'por_rendir' => 'Por rendir', 'en_rendicion' => 'En rendición', 'en_cuenta' => 'Llegó con la nómina', 'sin_nomina' => 'Llegó sin nómina', 'comercio' => 'Pago de comercio'];

                    return [
                        (string) ($fila['persona'] ?? ''),
                        trim((string) ($fila['tarjeta'] ?? 'Sin tarjeta identificada') . ' ' . (($fila['ultimos4'] ?? '') !== '' ? '****' . $fila['ultimos4'] : '')),
                        (string) $fila['detalle'],
                        (string) $fila['sentido'] === 'abono' ? 'Abono' : 'Cargo',
                        (float) $fila['monto'] > 0 ? $dinero($fila['monto'], (string) ($fila['moneda'] ?? $moneda)) : 'Sin monto',
                        $estados[$fila['estado']] ?? (string) $fila['estado'],
                    ];
                }),
            ],
            'cuentas' => self::cuentas($datos['cuentas'] ?? [], $dinero),
            default => [],
        };
    }

    /**
     * @param list<array<string, mixed>> $filas
     * @param callable(array<string, mixed>): list<string> $fila
     *
     * @return array{titulo: string, headers: list<string>, filas: list<list<string>>}
     */
    private static function detalle(string $titulo, array $headers, array $filas, callable $fila): array
    {
        $cuerpo = [];
        foreach ($filas as $item) {
            if (is_array($item)) {
                $cuerpo[] = $fila($item);
            }
        }

        return ['titulo' => $titulo, 'headers' => $headers, 'filas' => $cuerpo];
    }

    /**
     * @param list<array<string, mixed>> $filas
     * @param callable(array<string, mixed>): list<string> $fila
     *
     * @return array{titulo: string, headers: list<string>, filas: list<list<string>>}
     */
    private static function grupo(string $titulo, array $headers, array $filas, callable $fila): array
    {
        return self::detalle($titulo, $headers, $filas, $fila);
    }

    /**
     * @param array<string, mixed> $datos
     * @param callable(mixed, ?string=): string $dinero
     *
     * @return list<array{titulo: string, headers: list<string>, filas: list<list<string>>}>
     */
    private static function asientos(array $datos, callable $dinero): array
    {
        $tablas = [
            self::grupo('Por persona', ['Persona', 'Total'], $datos['por_usuario'] ?? [], static fn (array $fila): array => [
                (string) $fila['nombre'], $dinero($fila['monto']),
            ]),
        ];
        foreach ($datos['por_mes'] ?? [] as $grupo) {
            if (! is_array($grupo)) {
                continue;
            }
            $lineas = is_array($grupo['lineas'] ?? null) ? $grupo['lineas'] : [];
            $lineas[] = ['cuenta' => 'Total del mes', 'monto' => $grupo['total'] ?? 0];
            $tablas[] = self::detalle((string) ($grupo['mes'] ?? 'Mes'), ['Cuenta', 'Total'], $lineas, static fn (array $fila): array => [
                (string) $fila['cuenta'], $dinero($fila['monto']),
            ]);
        }
        $tablas[] = self::detalle('Detalle', ['Fecha', 'Persona', 'Cuenta', 'Gasto', 'Monto'], $datos['filas'] ?? [], static fn (array $fila): array => [
            (string) $fila['fecha'], (string) $fila['nombre'], (string) $fila['cuenta'], (string) $fila['concepto'], $dinero($fila['monto']),
        ]);

        return $tablas;
    }

    /**
     * @param list<array<string, mixed>> $filas
     * @param callable(mixed, ?string=): string $dinero
     *
     * @return list<array{titulo: string, headers: list<string>, filas: list<list<string>>}>
     */
    private static function socios(array $filas, callable $dinero): array
    {
        $tablas = [];
        foreach ($filas as $fila) {
            if (! is_array($fila)) {
                continue;
            }
            $estado = (string) ($fila['estado'] ?? '') === 'recuperado' ? 'Recuperado' : 'Por recuperar';
            $tablas[] = self::detalle((string) $fila['entidad'] . ' · ' . $estado, ['Documento', 'Forma de pago', 'Monto'], $fila['lineas'] ?? [], static fn (array $linea): array => [
                (string) $linea['motivo'], (string) $linea['forma'], $dinero($linea['monto']),
            ]);
        }

        return $tablas === [] ? [self::detalle('Socios', ['Documento', 'Forma de pago', 'Monto'], [], static fn (): array => [])] : $tablas;
    }

    /**
     * @param list<array<string, mixed>> $cuentas
     * @param callable(mixed, ?string=): string $dinero
     *
     * @return list<array{titulo: string, headers: list<string>, filas: list<list<string>>}>
     */
    private static function cuentas(array $cuentas, callable $dinero): array
    {
        $tablas = [];
        foreach ($cuentas as $cuenta) {
            if (! is_array($cuenta)) {
                continue;
            }
            $movimientos = is_array($cuenta['movimientos'] ?? null) ? $cuenta['movimientos'] : [];
            $movimientos[] = ['fecha' => '', 'glosa' => 'Total', 'auxiliar' => '', 'debe' => $cuenta['debe'] ?? 0, 'haber' => $cuenta['haber'] ?? 0];
            $tablas[] = self::detalle(
                trim((string) $cuenta['codigo'] . ' ' . $cuenta['nombre']) . ' · saldo ' . $dinero($cuenta['saldo'] ?? 0),
                ['Fecha', 'Glosa', 'Auxiliar', 'Debe', 'Haber'],
                $movimientos,
                static fn (array $fila): array => [
                    (string) ($fila['fecha'] ?? ''),
                    (string) ($fila['glosa'] ?? ''),
                    (string) ($fila['auxiliar'] ?? ''),
                    (float) ($fila['debe'] ?? 0) > 0 ? $dinero($fila['debe']) : '',
                    (float) ($fila['haber'] ?? 0) > 0 ? $dinero($fila['haber']) : '',
                ],
            );
        }

        return $tablas === [] ? [self::detalle('Cuentas', ['Fecha', 'Glosa', 'Auxiliar', 'Debe', 'Haber'], [], static fn (): array => [])] : $tablas;
    }
}
