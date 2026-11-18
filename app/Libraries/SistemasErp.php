<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Softnet y Odoo consumen lo que Rindete cerró.
 * Softnet publica una API de documentos tributarios y no recibe asientos ni nómina:
 * la carga queda en Contabilidad y en Remuneraciones.
 * Odoo 19 crea el asiento en account.move y lo publica; la nómina es una novedad del período.
 */
class SistemasErp
{
    /** @var array<string, array{nombre: string, detalle: string}> */
    public const CATALOGO = [
        'softnet' => [
            'nombre'  => 'Softnet',
            'detalle' => 'La API pública emite documentos tributarios y no recibe asientos ni nómina. Lo cerrado se carga en Contabilidad y en Remuneraciones.',
        ],
        'odoo' => [
            'nombre'  => 'Odoo',
            'detalle' => 'El preasiento se crea en account.move y se publica. La nómina entra como novedad del período, por el RUT del trabajador.',
        ],
    ];

    /**
     * @param array<string, mixed> $comprobante
     *
     * @return array<string, mixed>
     */
    public static function preasiento(string $sistema, array $comprobante): array
    {
        $lineas = [];
        foreach ($comprobante['lineas'] as $linea) {
            $lineas[] = [
                'orden'    => (int) $linea['orden'],
                'cuenta'   => (string) $linea['codigo'],
                'nombre'   => (string) $linea['nombre'],
                'glosa'    => (string) $linea['glosa'],
                'debe'     => round((float) $linea['debe'], 2),
                'haber'    => round((float) $linea['haber'], 2),
                'auxiliar' => (string) $linea['auxiliar'],
            ];
        }
        $documento = [
            'id'        => (int) $comprobante['id'],
            'estado'    => (string) $comprobante['estado'],
            'tipo'      => (string) $comprobante['tipo'],
            'fecha'     => (string) $comprobante['fecha'],
            'glosa'     => (string) $comprobante['glosa'],
            'moneda'    => (string) $comprobante['moneda'],
            'debe'      => round((float) $comprobante['debe'], 2),
            'haber'     => round((float) $comprobante['haber'], 2),
            'referencia'=> (string) ($comprobante['erp_ref'] ?? ''),
            'lineas'    => $lineas,
        ];
        $documento['proceso'] = $sistema === 'odoo'
            ? self::asientoOdoo($documento)
            : self::asientoSoftnet();

        return $documento;
    }

    /**
     * @param array<string, mixed> $nomina
     *
     * @return array<string, mixed>
     */
    public static function nomina(string $sistema, array $nomina): array
    {
        $lineas = [];
        foreach ($nomina['lineas'] as $linea) {
            $lineas[] = [
                'solicitud_id' => (int) $linea['solicitud_id'],
                'nombre'       => (string) $linea['nombre'],
                'rut'          => (string) $linea['rut'],
                'motivo'       => (string) $linea['motivo'],
                'monto'        => round((float) $linea['monto'], 2),
                'moneda'       => (string) $linea['moneda'],
            ];
        }
        $documento = [
            'id'         => (int) $nomina['id'],
            'estado'     => (string) $nomina['estado'],
            'anio'       => (int) $nomina['anio'],
            'mes'        => (int) $nomina['mes'],
            'referencia' => (string) ($nomina['erp_ref'] ?? ''),
            'lineas'     => $lineas,
        ];
        $documento['proceso'] = $sistema === 'odoo'
            ? self::nominaOdoo($documento)
            : self::nominaSoftnet();

        return $documento;
    }

    /**
     * @param array<string, mixed> $documento
     *
     * @return array<string, mixed>
     */
    private static function asientoOdoo(array $documento): array
    {
        $lineas = [];
        foreach ($documento['lineas'] as $linea) {
            $lineas[] = [0, 0, [
                'name'         => $linea['glosa'],
                'debit'        => $linea['debe'],
                'credit'       => $linea['haber'],
                'account_code' => $linea['cuenta'],
                'partner_vat'  => $linea['auxiliar'],
            ]];
        }

        return [
            'sistema' => 'odoo',
            'pasos'   => [
                [
                    'ruta'   => '/json/2/account.move/create',
                    'cuerpo' => [
                        'vals_list' => [[
                            'move_type' => 'entry',
                            'date'      => $documento['fecha'],
                            'ref'       => 'rindete:preasiento:' . $documento['id'],
                            'line_ids'  => $lineas,
                        ]],
                    ],
                    'antes'  => 'Resuelve account_code en account.account por code, y partner_vat en res.partner por vat. La clave API va como Authorization: bearer y necesita alcance rpc.',
                ],
                [
                    'ruta'   => '/json/2/account.move/action_post',
                    'cuerpo' => ['ids' => ['<id devuelto por create>']],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function asientoSoftnet(): array
    {
        return [
            'sistema' => 'softnet',
            'modulo'  => 'Contabilidad',
            'api'     => 'https://apisoftnet.cl/api/v1',
            'nota'    => 'Esa API emite DTE (33, 34, 39, 52, 56 y 61) y no recibe asientos. Carga este comprobante como asiento manual: fecha, glosa, líneas de debe y haber, y el RUT en el auxiliar.',
        ];
    }

    /**
     * @param array<string, mixed> $documento
     *
     * @return array<string, mixed>
     */
    private static function nominaOdoo(array $documento): array
    {
        $lineas = [];
        foreach ($documento['lineas'] as $linea) {
            $lineas[] = [
                'vat'    => $linea['rut'],
                'nombre' => $linea['nombre'],
                'amount' => $linea['monto'],
                'name'   => $linea['motivo'],
            ];
        }

        return [
            'sistema' => 'odoo',
            'modelo'  => 'hr.payslip.input',
            'periodo' => sprintf('%04d-%02d', $documento['anio'], $documento['mes']),
            'nota'    => 'Cada línea es una novedad del período para el trabajador. El código de entrada lo define la regla salarial del fondo por rendir.',
            'lineas'  => $lineas,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function nominaSoftnet(): array
    {
        return [
            'sistema' => 'softnet',
            'modulo'  => 'Remuneraciones',
            'api'     => 'https://apisoftnet.cl/api/v1',
            'nota'    => 'Esa API no recibe la nómina. Carga cada línea como novedad del período: RUT, motivo y monto.',
        ];
    }
}
