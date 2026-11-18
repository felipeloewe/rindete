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

    public static function clase(string $estado): string
    {
        $conocidos = ['observado', 'legalizado', 'en_rendicion', 'aprobado', 'devuelto'];

        return 'chip chip-' . (in_array($estado, $conocidos, true) ? $estado : 'legalizado');
    }

    public static function rendicion(string $estado): string
    {
        return [
            'abierta' => 'Abierta',
            'enviada' => 'Enviada',
        ][$estado] ?? $estado;
    }

    public static function claseRendicion(string $estado): string
    {
        return $estado === 'enviada' ? 'chip chip-en_rendicion' : 'chip chip-legalizado';
    }

    public static function comprobante(string $estado): string
    {
        return [
            'propuesto' => 'Preasiento',
            'cerrado'   => 'Cerrado',
            'asiento'   => 'Asiento',
        ][$estado] ?? 'Preasiento';
    }

    public static function nomina(string $estado): string
    {
        return [
            'cerrada'   => 'Lista para el ERP',
            'consumida' => 'El ERP ya la tomó',
        ][$estado] ?? $estado;
    }

    public static function solicitud(string $estado): string
    {
        return [
            'detenida'      => 'Detenida',
            'en_aceptacion' => 'En aceptación',
            'aceptada'      => 'Aceptada',
            'rechazada'     => 'Rechazada',
            'entregada'     => 'Entregada',
        ][$estado] ?? $estado;
    }

    public static function claseSolicitud(string $estado): string
    {
        return [
            'detenida'      => 'chip chip-observado',
            'en_aceptacion' => 'chip chip-en_rendicion',
            'aceptada'      => 'chip chip-legalizado',
            'rechazada'     => 'chip chip-devuelto',
            'entregada'     => 'chip chip-aprobado',
        ][$estado] ?? 'chip chip-legalizado';
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
        if ((int) ($gasto['fuera_tipo'] ?? 0) === 1) {
            $lista[] = 'Supera el monto del tipo de documento.';
        }
        if ((int) ($gasto['no_aceptado'] ?? 0) === 1) {
            $lista[] = 'Esa categoría no está aceptada para esta persona.';
        }
        if ((int) ($gasto['fiscal_incompleto'] ?? 0) === 1) {
            $lista[] = 'Faltan datos del documento.';
        }

        return $lista;
    }
}
