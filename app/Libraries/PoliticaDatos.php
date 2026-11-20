<?php

declare(strict_types=1);

namespace App\Libraries;

class PoliticaDatos
{
    public const VERSION = '2026-11-20b';

    /**
     * @param array<string, mixed>|null $fila
     */
    public static function aceptada(?array $fila): bool
    {
        return is_array($fila)
            && (string) ($fila['politica_version'] ?? '') === self::VERSION
            && (string) ($fila['politica_aceptada_en'] ?? '') !== '';
    }

    /**
     * @return array{politica_version: string, politica_aceptada_en: string}
     */
    public static function marca(): array
    {
        return [
            'politica_version'     => self::VERSION,
            'politica_aceptada_en' => date('Y-m-d H:i:s'),
        ];
    }
}
