<?php

declare(strict_types=1);

namespace App\Libraries;

class Paises
{
    /**
     * @return array<string, array{nombre: string, moneda: string, autoridad: string, id_fiscal: string, ejemplo: string, id_proveedor: string, codigo: string, codigo_ayuda: string, docs: list<string>}>
     */
    public static function catalogo(): array
    {
        return [
            'CL' => [
                'nombre' => 'Chile', 'moneda' => 'CLP', 'autoridad' => 'SII', 'id_fiscal' => 'RUT', 'ejemplo' => '12.345.678-5',
                'id_proveedor' => 'RUT proveedor', 'codigo' => 'Folio DTE', 'codigo_ayuda' => 'Folio del documento tributario electrónico',
                'docs' => ['Factura electrónica', 'Boleta', 'Nota de crédito', 'Comprobante de gasto', 'Otro'],
            ],
            'CO' => [
                'nombre' => 'Colombia', 'moneda' => 'COP', 'autoridad' => 'DIAN', 'id_fiscal' => 'NIT', 'ejemplo' => '900.123.456-8',
                'id_proveedor' => 'NIT proveedor', 'codigo' => 'CUFE', 'codigo_ayuda' => 'Código único de factura electrónica',
                'docs' => ['Factura electrónica', 'Documento equivalente', 'Cuenta de cobro', 'Documento soporte', 'Otro'],
            ],
            'MX' => [
                'nombre' => 'México', 'moneda' => 'MXN', 'autoridad' => 'SAT', 'id_fiscal' => 'RFC', 'ejemplo' => 'ABC010101XX4',
                'id_proveedor' => 'RFC proveedor', 'codigo' => 'UUID del CFDI', 'codigo_ayuda' => 'Folio fiscal del comprobante',
                'docs' => ['CFDI ingreso', 'CFDI egreso', 'Nota de venta', 'Recibo', 'Otro'],
            ],
            'SV' => [
                'nombre' => 'El Salvador', 'moneda' => 'USD', 'autoridad' => 'Ministerio de Hacienda', 'id_fiscal' => 'NIT', 'ejemplo' => '0614-050707-104-8',
                'id_proveedor' => 'NIT proveedor', 'codigo' => 'Código de generación', 'codigo_ayuda' => 'Código de generación del DTE',
                'docs' => ['DTE factura', 'DTE crédito fiscal', 'Ticket', 'Recibo', 'Otro'],
            ],
            'EC' => [
                'nombre' => 'Ecuador', 'moneda' => 'USD', 'autoridad' => 'SRI', 'id_fiscal' => 'RUC', 'ejemplo' => '1710034065001',
                'id_proveedor' => 'RUC proveedor', 'codigo' => 'Clave de acceso', 'codigo_ayuda' => 'Clave de acceso de la factura electrónica',
                'docs' => ['Factura electrónica', 'Nota de venta', 'Liquidación de compra', 'Recibo', 'Otro'],
            ],
            'PE' => [
                'nombre' => 'Perú', 'moneda' => 'PEN', 'autoridad' => 'SUNAT', 'id_fiscal' => 'RUC', 'ejemplo' => '20123456786',
                'id_proveedor' => 'RUC proveedor', 'codigo' => 'Serie y número', 'codigo_ayuda' => 'Serie y número del comprobante electrónico',
                'docs' => ['Factura electrónica', 'Boleta de venta', 'Recibo por honorarios', 'Ticket', 'Otro'],
            ],
        ];
    }

    /**
     * @return array{nombre: string, moneda: string, autoridad: string, id_fiscal: string, ejemplo: string, id_proveedor: string, codigo: string, codigo_ayuda: string, docs: list<string>}|null
     */
    public static function pais(string $codigo): ?array
    {
        return self::catalogo()[$codigo] ?? null;
    }
}
