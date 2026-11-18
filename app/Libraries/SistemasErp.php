<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Softnet y Odoo consumen lo que Rindete cerró.
 * Softnet publica una API de documentos tributarios. El asiento se carga en Contabilidad
 * y la nómina de fondos por rendir se crea en el banco, desde Tesorería.
 * Odoo crea el asiento en account.move y la nómina como pagos de salida.
 */
class SistemasErp
{
    /** @var array<string, array{nombre: string, detalle: string}> */
    public const CATALOGO = [
        'softnet' => [
            'nombre'  => 'Softnet',
            'detalle' => 'El token de su API se guarda en esta conexión. El preasiento va a Contabilidad. La nómina de fondos por rendir se crea en el banco, desde Tesorería.',
        ],
        'odoo' => [
            'nombre'  => 'Odoo',
            'detalle' => 'El token de su API se guarda en esta conexión. El preasiento se crea en account.move. La nómina de fondos por rendir sale como pagos desde el banco.',
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
                'cuenta_banco' => (string) ($linea['cuenta_banco'] ?? ''),
                'motivo'       => (string) $linea['motivo'],
                'monto'        => round((float) $linea['monto'], 2),
                'moneda'       => (string) $linea['moneda'],
            ];
        }
        $documento = [
            'id'         => (int) $nomina['id'],
            'tipo'       => 'fondos_por_rendir',
            'estado'     => (string) $nomina['estado'],
            'anio'       => (int) $nomina['anio'],
            'mes'        => (int) $nomina['mes'],
            'banco'      => (string) ($nomina['banco'] ?? ''),
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
        $pagos = [];
        foreach ($documento['lineas'] as $linea) {
            $pagos[] = [
                'payment_type'         => 'outbound',
                'partner_type'         => 'supplier',
                'amount'               => $linea['monto'],
                'partner_vat'          => $linea['rut'],
                'partner_bank_account' => $linea['cuenta_banco'],
                'ref'                  => 'rindete:nomina:' . $documento['id'] . ':' . $linea['solicitud_id'],
                'memo'                 => 'Fondo por rendir, ' . $linea['nombre'],
            ];
        }

        return [
            'sistema' => 'odoo',
            'modelo'  => 'account.payment',
            'banco'   => $documento['banco'],
            'nota'    => 'Una sola nómina de fondos por rendir. Cada línea es un pago de salida desde el banco de la empresa hacia la cuenta de la persona. No es una liquidación de remuneraciones.',
            'pagos'   => $pagos,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function nominaSoftnet(): array
    {
        return [
            'sistema' => 'softnet',
            'modulo'  => 'Tesorería',
            'api'     => 'https://apisoftnet.cl/api/v1',
            'nota'    => 'Una nómina de transferencias en el banco de la empresa. Cada línea es un fondo por rendir: RUT, cuenta de destino y monto. No es remuneración. La API pública no crea esa nómina; Tesorería o el banco la toman de este lote.',
        ];
    }
}
