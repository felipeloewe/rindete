<?php

declare(strict_types=1);

namespace App\Libraries;

class Estados
{
    public static function gasto(string $estado): string
    {
        return [
            'observado'    => 'Observado',
            'legalizado'   => 'Legalizado',
            'en_rendicion' => 'En rendición',
            'aprobado'     => 'Aprobado',
            'devuelto'     => 'Devuelto',
        ][$estado] ?? $estado;
    }

    /**
     * @param array<string, mixed> $gasto
     *
     * @return list<string>
     */
    public static function observaciones(array $gasto): array
    {
        $lista = [];
        if ((int) ($gasto['duplicado'] ?? 0) === 1) {
            $lista[] = 'Hay otro gasto igual.';
        }
        if ((int) ($gasto['fuera_politica'] ?? 0) === 1) {
            $lista[] = 'Supera el tope de la categoría.';
        }
        if ((int) ($gasto['sin_comprobante'] ?? 0) === 1) {
            $lista[] = 'Falta el comprobante.';
        }
        if ((int) ($gasto['fiscal_incompleto'] ?? 0) === 1) {
            $lista[] = 'Faltan datos del documento.';
        }

        return $lista;
    }
}
