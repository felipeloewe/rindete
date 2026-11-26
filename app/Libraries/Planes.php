<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\UsuarioModel;

/**
 * El plan admite personas que rinden. El administrador no cuenta
 * ni rinde hasta que el superadmin lo marca como usuario rendidor.
 */
final class Planes
{
    public const SIN_MARCA = 'Esta persona no está marcada como usuario rendidor.';

    public const CODIGO   = 'free';
    public const USUARIOS = 4;
    public const BYTES    = 2 * 1024 * 1024 * 1024;

    public static function texto(): string
    {
        return 'Plan Free. Hasta 4 usuarios y 2 GB.';
    }

    /**
     * @return array{plan: string, cupo_usuarios: int, cupo_bytes: int}
     */
    public static function alta(): array
    {
        return [
            'plan'          => self::CODIGO,
            'cupo_usuarios' => self::USUARIOS,
            'cupo_bytes'    => self::BYTES,
        ];
    }

    public static function cabe(int $usado, int $entra, int $cupo): bool
    {
        if ($entra <= 0) {
            return true;
        }

        return ($usado + $entra) <= $cupo;
    }

    public static function puedeSumarUsuario(int $actuales, int $cupo = self::USUARIOS): bool
    {
        return $actuales < $cupo;
    }

    /**
     * @param array<string, mixed> $usuario
     */
    public static function esRendidor(array $usuario): bool
    {
        return (int) ($usuario['rinde'] ?? 0) === 1;
    }

    public static function rendidores(int $empresaId): int
    {
        return model(UsuarioModel::class)
            ->where('empresa_id', $empresaId)
            ->where('rinde', 1)
            ->countAllResults();
    }

    /**
     * @param array<string, mixed> $empresa
     */
    public static function avisoCupo(array $empresa): string
    {
        $cupo = (int) ($empresa['cupo_usuarios'] ?? self::USUARIOS);
        if ((string) ($empresa['plan'] ?? '') === 'free') {
            return 'El plan Free admite ' . $cupo . ' usuarios.';
        }

        return 'El plan admite ' . $cupo . ($cupo === 1 ? ' usuario.' : ' usuarios.');
    }
}
