<?php

function paises() {
    return [
        'CL' => [
            'nombre' => 'Chile',
            'sigla' => 'SII',
            'moneda' => 'CLP',
            'autoridad' => 'SII',
            'id_fiscal' => 'RUT',
            'ejemplo' => '12.345.678-5',
            'id_proveedor' => 'RUT proveedor',
            'codigo' => 'Folio DTE',
            'codigo_ayuda' => 'Folio del documento tributario electrónico',
            'docs' => ['Factura electrónica', 'Boleta', 'Nota de crédito', 'Comprobante de gasto', 'Otro'],
        ],
        'CO' => [
            'nombre' => 'Colombia',
            'sigla' => 'DIAN',
            'moneda' => 'COP',
            'autoridad' => 'DIAN',
            'id_fiscal' => 'NIT',
            'ejemplo' => '900.123.456-8',
            'id_proveedor' => 'NIT proveedor',
            'codigo' => 'CUFE',
            'codigo_ayuda' => 'Código único de factura electrónica',
            'docs' => ['Factura electrónica', 'Documento equivalente', 'Cuenta de cobro', 'Documento soporte', 'Otro'],
        ],
        'MX' => [
            'nombre' => 'México',
            'sigla' => 'SAT',
            'moneda' => 'MXN',
            'autoridad' => 'SAT',
            'id_fiscal' => 'RFC',
            'ejemplo' => 'ABC010101XX4',
            'id_proveedor' => 'RFC proveedor',
            'codigo' => 'UUID del CFDI',
            'codigo_ayuda' => 'Folio fiscal del comprobante',
            'docs' => ['CFDI ingreso', 'CFDI egreso', 'Nota de venta', 'Recibo', 'Otro'],
        ],
        'SV' => [
            'nombre' => 'El Salvador',
            'sigla' => 'MH',
            'moneda' => 'USD',
            'autoridad' => 'Ministerio de Hacienda',
            'id_fiscal' => 'NIT',
            'ejemplo' => '0614-050707-104-8',
            'id_proveedor' => 'NIT proveedor',
            'codigo' => 'Código de generación',
            'codigo_ayuda' => 'Código de generación del DTE',
            'docs' => ['DTE factura', 'DTE crédito fiscal', 'Ticket', 'Recibo', 'Otro'],
        ],
        'EC' => [
            'nombre' => 'Ecuador',
            'sigla' => 'SRI',
            'moneda' => 'USD',
            'autoridad' => 'SRI',
            'id_fiscal' => 'RUC',
            'ejemplo' => '1710034065001',
            'id_proveedor' => 'RUC proveedor',
            'codigo' => 'Clave de acceso',
            'codigo_ayuda' => 'Clave de acceso de la factura electrónica',
            'docs' => ['Factura electrónica', 'Nota de venta', 'Liquidación de compra', 'Recibo', 'Otro'],
        ],
        'PE' => [
            'nombre' => 'Perú',
            'sigla' => 'SUNAT',
            'moneda' => 'PEN',
            'autoridad' => 'SUNAT',
            'id_fiscal' => 'RUC',
            'ejemplo' => '20123456786',
            'id_proveedor' => 'RUC proveedor',
            'codigo' => 'Serie y número',
            'codigo_ayuda' => 'Serie y número del comprobante electrónico',
            'docs' => ['Factura electrónica', 'Boleta de venta', 'Recibo por honorarios', 'Ticket', 'Otro'],
        ],
    ];
}

function pais($codigo) {
    $todos = paises();
    return $todos[$codigo] ?? $todos['CO'];
}

function topes_por_moneda($moneda) {
    $tablas = [
        'CLP' => ['Alimentación' => 45000, 'Transporte' => 30000, 'Alojamiento' => 180000, 'Combustible' => 80000, 'Representación' => 120000, 'Suministros' => 60000],
        'COP' => ['Alimentación' => 120000, 'Transporte' => 80000, 'Alojamiento' => 450000, 'Combustible' => 200000, 'Representación' => 300000, 'Suministros' => 150000],
        'MXN' => ['Alimentación' => 900, 'Transporte' => 600, 'Alojamiento' => 3500, 'Combustible' => 1500, 'Representación' => 2500, 'Suministros' => 1200],
        'USD' => ['Alimentación' => 45, 'Transporte' => 30, 'Alojamiento' => 180, 'Combustible' => 70, 'Representación' => 120, 'Suministros' => 60],
        'PEN' => ['Alimentación' => 120, 'Transporte' => 80, 'Alojamiento' => 450, 'Combustible' => 180, 'Representación' => 280, 'Suministros' => 150],
    ];
    return $tablas[$moneda] ?? $tablas['USD'];
}

function categorias_base($moneda) {
    $topes = topes_por_moneda($moneda);
    $meta = [
        'Alimentación' => 'Comidas, cafés y viáticos de alimentación',
        'Transporte' => 'Taxis, apps, buses y traslados',
        'Alojamiento' => 'Hoteles y hospedaje',
        'Combustible' => 'Gasolina y carga de vehículos',
        'Representación' => 'Reuniones con clientes y socios',
        'Suministros' => 'Material, papelería y compras menores',
    ];
    $lista = [];
    foreach ($meta as $nombre => $detalle) {
        $lista[] = ['nombre' => $nombre, 'detalle' => $detalle, 'tope' => $topes[$nombre]];
    }
    return $lista;
}
