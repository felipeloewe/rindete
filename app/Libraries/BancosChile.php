<?php

declare(strict_types=1);

namespace App\Libraries;

class BancosChile
{
    /**
     * Códigos de tres dígitos que pide el archivo de Pago de Nómina en Línea.
     *
     * @var array<string, string>
     */
    public const CODIGOS = [
        '001' => 'Banco de Chile',
        '009' => 'Banco Internacional',
        '012' => 'Banco Estado',
        '014' => 'Scotiabank',
        '016' => 'BCI',
        '017' => 'Banco do Brasil',
        '027' => 'Corpbanca',
        '028' => 'Banco BICE',
        '031' => 'HSBC',
        '037' => 'Banco Santander',
        '039' => 'Banco Itaú',
        '041' => 'JP Morgan',
        '043' => 'Banco de la Nación Argentina',
        '045' => 'Bank of Tokyo-Mitsubishi',
        '049' => 'Banco Security',
        '051' => 'Banco Falabella',
        '052' => 'Deutsche Bank',
        '053' => 'Banco Ripley',
        '054' => 'Rabobank',
        '055' => 'Banco Consorcio',
        '056' => 'Banco Penta',
        '057' => 'Banco Paris',
        '059' => 'Banco BTG Pactual',
        '504' => 'BBVA',
    ];

    public static function existe(string $codigo): bool
    {
        return isset(self::CODIGOS[$codigo]);
    }
}
