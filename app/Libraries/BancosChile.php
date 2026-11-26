<?php

declare(strict_types=1);

namespace App\Libraries;

class BancosChile
{
    /**
     * Códigos SBIF de tres dígitos que pide el archivo de Pago de Nómina en Línea.
     *
     * @var array<string, string>
     */
    public const BANCOS = [
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

    /**
     * Códigos institucionales de la CMF para emisores de tarjetas de pago
     * con provisión de fondos no bancarios. El archivo de nómina usa el mismo
     * campo de tres dígitos.
     *
     * @var array<string, string>
     */
    public const TPPF = [
        '697' => 'La Polar Prepago',
        '699' => 'Tricard',
        '729' => 'Los Héroes',
        '730' => 'Tenpo Payments',
        '732' => 'TAPP',
        '738' => 'Global Card',
        '739' => 'Haulmer Prepago',
        '741' => 'Copec Pay',
        '743' => 'Prex',
        '744' => 'SumUp',
        '746' => 'Fintual Prepago',
        '747' => 'Metro',
        '764' => 'Fintoc Pagos',
        '875' => 'Mercado Pago',
        '882' => 'Pomelo Tech',
    ];

    /**
     * @var array<string, string>
     */
    public const CODIGOS = self::BANCOS + self::TPPF;

    /**
     * @return array<string, array<string, string>>
     */
    public static function grupos(): array
    {
        return [
            'Bancos' => self::BANCOS,
            'TPPF'   => self::TPPF,
        ];
    }

    public static function existe(string $codigo): bool
    {
        return isset(self::CODIGOS[$codigo]);
    }
}
