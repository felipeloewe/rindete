<?php

declare(strict_types=1);

namespace App\Libraries;

class GastoEvaluador
{
    /**
     * @param array<string, mixed>                $gasto
     * @param array<string, mixed>                $empresa
     * @param array<string, mixed>|null           $categoria
     * @param list<array<string, mixed>>          $otros
     *
     * @return array<string, mixed>
     */
    public function evaluar(array $gasto, array $empresa, ?array $categoria, array $otros, ?int $exceptoId = null): array
    {
        $duplicado = false;
        if ((int) ($empresa['detectar_duplicados'] ?? 1) === 1) {
            $dias   = (int) ($empresa['dias_duplicado'] ?? 3);
            $fecha  = strtotime((string) $gasto['fecha']) ?: 0;
            foreach ($otros as $otro) {
                if ($exceptoId !== null && (int) $otro['id'] === $exceptoId) {
                    continue;
                }
                $mismoDocumento = trim((string) $gasto['num_doc']) !== ''
                    && Montos::texto((string) $otro['num_doc']) === Montos::texto((string) $gasto['num_doc'])
                    && Montos::texto((string) $otro['id_fiscal']) === Montos::texto((string) $gasto['id_fiscal']);
                $mismaCuenta = Montos::texto((string) $otro['proveedor']) === Montos::texto((string) $gasto['proveedor'])
                    && abs((float) $otro['monto'] - (float) $gasto['monto']) < 0.001
                    && abs((strtotime((string) $otro['fecha']) ?: 0) - $fecha) <= ($dias * 86400);
                if ($mismoDocumento || $mismaCuenta) {
                    $duplicado = true;
                    break;
                }
            }
        }

        $fuera  = $categoria !== null && (float) $categoria['tope'] > 0 && (float) $gasto['monto'] > (float) $categoria['tope'];
        $desde  = (float) ($empresa['comprobante_desde'] ?? 1);
        $sin    = ((string) ($gasto['comprobante'] ?? '')) === '' && (float) $gasto['monto'] >= $desde;
        $fiscal = trim((string) ($gasto['id_fiscal'] ?? '')) === ''
            || trim((string) ($gasto['num_doc'] ?? '')) === ''
            || trim((string) ($gasto['tipo_doc'] ?? '')) === '';
        $estado = (string) ($gasto['estado'] ?? 'legalizado');
        if (! in_array($estado, ['en_rendicion', 'aprobado'], true)) {
            $estado = ($duplicado || $fuera || $sin) ? 'observado' : 'legalizado';
        }

        $gasto['duplicado']          = $duplicado ? 1 : 0;
        $gasto['fuera_politica']     = $fuera ? 1 : 0;
        $gasto['sin_comprobante']    = $sin ? 1 : 0;
        $gasto['fiscal_incompleto']  = $fiscal ? 1 : 0;
        $gasto['estado']             = $estado;

        return $gasto;
    }
}
