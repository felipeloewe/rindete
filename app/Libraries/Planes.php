<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * El alta pública entra al plan Free: 4 usuarios y 2 GB.
 */
final class Planes
{
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
}
