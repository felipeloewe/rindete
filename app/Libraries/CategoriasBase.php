<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\CategoriaModel;

class CategoriasBase
{
    /**
     * @return list<array{nombre: string, detalle: string, tope: int}>
     */
    public static function paraMoneda(string $moneda): array
    {
        $topes = [
            'CLP' => ['Alimentación' => 45000, 'Transporte' => 30000, 'Alojamiento' => 180000, 'Combustible' => 80000, 'Representación' => 120000, 'Suministros' => 60000],
            'COP' => ['Alimentación' => 120000, 'Transporte' => 80000, 'Alojamiento' => 450000, 'Combustible' => 200000, 'Representación' => 300000, 'Suministros' => 150000],
            'MXN' => ['Alimentación' => 900, 'Transporte' => 600, 'Alojamiento' => 3500, 'Combustible' => 1500, 'Representación' => 2500, 'Suministros' => 1200],
            'USD' => ['Alimentación' => 45, 'Transporte' => 30, 'Alojamiento' => 180, 'Combustible' => 70, 'Representación' => 120, 'Suministros' => 60],
            'PEN' => ['Alimentación' => 120, 'Transporte' => 80, 'Alojamiento' => 450, 'Combustible' => 180, 'Representación' => 280, 'Suministros' => 150],
        ];
        $detalle = [
            'Alimentación'   => 'Comidas, cafés y viáticos de alimentación',
            'Transporte'     => 'Taxis, apps, buses y traslados',
            'Alojamiento'    => 'Hoteles y hospedaje',
            'Combustible'    => 'Gasolina y carga de vehículos',
            'Representación' => 'Reuniones con clientes y socios',
            'Suministros'    => 'Material, papelería y compras menores',
        ];
        $tabla = $topes[$moneda] ?? $topes['USD'];
        $lista = [];
        foreach ($detalle as $nombre => $texto) {
            $lista[] = ['nombre' => $nombre, 'detalle' => $texto, 'tope' => $tabla[$nombre]];
        }

        return $lista;
    }

    public static function sembrar(int $empresaId, string $moneda): void
    {
        $categorias = model(CategoriaModel::class);
        if ($categorias->where('empresa_id', $empresaId)->countAllResults() > 0) {
            return;
        }

        foreach (self::paraMoneda($moneda) as $base) {
            $categorias->insert([
                'empresa_id' => $empresaId,
                'nombre'     => $base['nombre'],
                'detalle'    => $base['detalle'],
                'tope'       => $base['tope'],
                'activa'     => 1,
            ]);
        }
    }
}
