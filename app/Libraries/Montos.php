<?php

declare(strict_types=1);

namespace App\Libraries;

class Montos
{
    public static function parse(string $raw): ?float
    {
        $raw = trim(str_replace([' ', '$'], '', $raw));
        if ($raw === '') {
            return null;
        }
        if (preg_match('/,\d{1,2}$/', $raw) === 1) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } else {
            $raw = str_replace(',', '', $raw);
        }
        if (! is_numeric($raw)) {
            return null;
        }
        $number = round((float) $raw, 2);
        if ($number < 0 || $number > 9999999999) {
            return null;
        }

        return $number;
    }

    public static function fecha(string $raw): ?string
    {
        $raw = trim($raw);
        $dt  = \DateTime::createFromFormat('Y-m-d', $raw);
        if ($dt === false || $dt->format('Y-m-d') !== $raw) {
            return null;
        }
        $year = (int) $dt->format('Y');
        if ($year < 2000 || $year > (int) date('Y') + 1) {
            return null;
        }

        return $raw;
    }

    public static function formato(float $monto, string $moneda): string
    {
        $decimales = in_array($moneda, ['CLP', 'COP'], true) ? 0 : 2;

        return $moneda . ' ' . number_format($monto, $decimales, ',', '.');
    }

    public static function texto(string $valor): string
    {
        $valor = trim(mb_strtolower($valor));

        return (string) preg_replace('/\s+/', ' ', $valor);
    }
}
