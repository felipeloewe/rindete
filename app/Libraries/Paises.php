<?php

declare(strict_types=1);

namespace App\Libraries;

class Paises
{
    /**
     * @return array<string, array{nombre: string, moneda: string, id_fiscal: string, ejemplo: string}>
     */
    public static function catalogo(): array
    {
        return [
            'CL' => ['nombre' => 'Chile', 'moneda' => 'CLP', 'id_fiscal' => 'RUT', 'ejemplo' => '12.345.678-5'],
            'CO' => ['nombre' => 'Colombia', 'moneda' => 'COP', 'id_fiscal' => 'NIT', 'ejemplo' => '900.123.456-8'],
            'MX' => ['nombre' => 'México', 'moneda' => 'MXN', 'id_fiscal' => 'RFC', 'ejemplo' => 'ABC010101XX4'],
            'SV' => ['nombre' => 'El Salvador', 'moneda' => 'USD', 'id_fiscal' => 'NIT', 'ejemplo' => '0614-050707-104-8'],
            'EC' => ['nombre' => 'Ecuador', 'moneda' => 'USD', 'id_fiscal' => 'RUC', 'ejemplo' => '1710034065001'],
            'PE' => ['nombre' => 'Perú', 'moneda' => 'PEN', 'id_fiscal' => 'RUC', 'ejemplo' => '20123456786'],
        ];
    }

    /**
     * @return array{nombre: string, moneda: string, id_fiscal: string, ejemplo: string}|null
     */
    public static function pais(string $codigo): ?array
    {
        return self::catalogo()[$codigo] ?? null;
    }
}
