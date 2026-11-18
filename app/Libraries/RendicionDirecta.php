<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\CuentaModel;

/**
 * Parámetros de quien abre una rendición sin solicitud de fondos.
 * El tope y los conceptos de viático y asignación son los de la persona si existen, y si no los de la empresa.
 */
final class RendicionDirecta
{
    public static function autorizada(array $usuario): bool
    {
        return (int) ($usuario['puede_rendir_sin_solicitud'] ?? 0) === 1;
    }

    /**
     * @param array<string, mixed> $usuario
     * @param array<string, mixed> $empresa
     *
     * @return array{monto: float, propio: bool, viatico: array<string, mixed>|null, asignacion: array<string, mixed>|null}
     */
    public static function de(array $usuario, array $empresa): array
    {
        $propio    = $usuario['monto_maximo_rendicion'] ?? null;
        $usaPropio = $propio !== null && $propio !== '';

        return [
            'monto'      => $usaPropio ? (float) $propio : (float) ($empresa['monto_maximo_rendicion'] ?? 0),
            'propio'     => $usaPropio,
            'viatico'    => self::concepto((int) $empresa['id'], (int) $usuario['id'], 'viatico'),
            'asignacion' => self::concepto((int) $empresa['id'], (int) $usuario['id'], 'asignacion'),
        ];
    }

    /**
     * @param array<string, mixed>|null $cuenta
     */
    public static function etiqueta(?array $cuenta): string
    {
        if ($cuenta === null) {
            return 'Sin concepto';
        }
        $de = (int) ($cuenta['usuario_id'] ?? 0) > 0 ? 'propio' : 'de la empresa';

        return $cuenta['codigo'] . ' ' . $cuenta['nombre'] . ' · ' . $de;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function concepto(int $empresaId, int $usuarioId, string $concepto): ?array
    {
        $propia = model(CuentaModel::class)
            ->where('empresa_id', $empresaId)
            ->where('clase', 'remuneracion')
            ->where('concepto', $concepto)
            ->where('usuario_id', $usuarioId)
            ->where('activa', 1)
            ->orderBy('id', 'ASC')
            ->first();
        if (is_array($propia)) {
            return $propia;
        }

        $general = model(CuentaModel::class)
            ->where('empresa_id', $empresaId)
            ->where('clase', 'remuneracion')
            ->where('concepto', $concepto)
            ->where('usuario_id', null)
            ->where('activa', 1)
            ->orderBy('id', 'ASC')
            ->first();

        return is_array($general) ? $general : null;
    }
}
