<?php

declare(strict_types=1);

namespace App\Libraries;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

class ZonaHoraria
{
    public const DEFAULT = 'America/Santiago';

    /**
     * @param array<string, mixed>|null $empresa
     */
    public static function deEmpresa(?array $empresa): string
    {
        $zona = trim((string) ($empresa['timezone'] ?? ''));
        if ($zona !== '' && self::valida($zona)) {
            return $zona;
        }

        return self::DEFAULT;
    }

    public static function valida(string $zona): bool
    {
        try {
            new DateTimeZone($zona);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Inicio del mes calendario en la zona de la empresa, como UTC/local wall clock
     * comparable con columnas DATETIME guardadas con date() de la app.
     *
     * @param array<string, mixed>|null $empresa
     */
    public static function inicioMesCupo(?array $empresa, ?DateTimeImmutable $ahora = null): string
    {
        $tz    = new DateTimeZone(self::deEmpresa($empresa));
        $ahora ??= new DateTimeImmutable('now', $tz);

        return $ahora->setTimezone($tz)->modify('first day of this month')->setTime(0, 0, 0)->format('Y-m-d H:i:s');
    }

    /**
     * @return list<string>
     */
    public static function opciones(): array
    {
        return [
            'America/Santiago',
            'America/Bogota',
            'America/Mexico_City',
            'America/Lima',
            'America/Buenos_Aires',
            'America/Sao_Paulo',
            'UTC',
        ];
    }
}
