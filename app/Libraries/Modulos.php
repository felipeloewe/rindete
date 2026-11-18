<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Lo gratuito está siempre activo. Cada función de pago es un módulo del catálogo
 * y la empresa lo contrata cuando lo necesita. El precio todavía no está definido:
 * contratar deja el módulo activo en esa empresa y se puede dejar después.
 */
final class Modulos
{
    /** @var array<string, string> */
    public const GRATIS = [
        'gastos'      => 'Gastos y comprobantes',
        'rendiciones' => 'Rendiciones',
        'fondos'      => 'Fondos por rendir',
        'personas'    => 'Personas',
        'informes'    => 'Informes del período',
        'parametros'  => 'Parametrización de la carga',
    ];

    /** @var array<string, array{nombre: string, detalle: string}> */
    public const CATALOGO = [
        'asientos' => [
            'nombre'  => 'Asientos',
            'detalle' => 'El reporte de asientos del período, por persona y por mes.',
        ],
        'preasientos' => [
            'nombre'  => 'Preasientos',
            'detalle' => 'La salida de fondos y el traspaso al gasto, en comprobantes del mes.',
        ],
        'cuentas' => [
            'nombre'  => 'Seguimiento de cuentas',
            'detalle' => 'Los movimientos de cada cuenta: el fondo nace cuando sale el dinero y pasa al gasto cuando se rinde.',
        ],
    ];

    /**
     * @param array<string, mixed> $empresa
     */
    public static function activo(array $empresa, string $codigo): bool
    {
        if (isset(self::GRATIS[$codigo])) {
            return true;
        }
        if (! isset(self::CATALOGO[$codigo])) {
            return false;
        }

        return db_connect()->table('empresa_modulos')
            ->where('empresa_id', (int) $empresa['id'])
            ->where('codigo', $codigo)
            ->countAllResults() > 0;
    }

    /**
     * @return list<string>
     */
    public static function contratados(int $empresaId): array
    {
        $codigos = [];
        $filas = db_connect()->table('empresa_modulos')
            ->where('empresa_id', $empresaId)
            ->orderBy('codigo', 'ASC')
            ->get()
            ->getResultArray();
        foreach ($filas as $fila) {
            $codigos[] = (string) $fila['codigo'];
        }

        return $codigos;
    }

    public static function contratar(int $empresaId, string $codigo): bool
    {
        if (! isset(self::CATALOGO[$codigo]) || self::activo(['id' => $empresaId], $codigo)) {
            return isset(self::CATALOGO[$codigo]);
        }
        db_connect()->table('empresa_modulos')->insert([
            'empresa_id' => $empresaId,
            'codigo'     => $codigo,
            'contratado' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public static function dejar(int $empresaId, string $codigo): void
    {
        db_connect()->table('empresa_modulos')
            ->where('empresa_id', $empresaId)
            ->where('codigo', $codigo)
            ->delete();
    }

    public static function aviso(string $codigo): string
    {
        $nombre = self::CATALOGO[$codigo]['nombre'] ?? 'Este módulo';

        return $nombre . ' no está contratado. El plan Free no lo incluye.';
    }

    public static function resumen(): string
    {
        return 'Free · 4 usuarios · 2 GB. Los módulos de contabilidad se contratan aparte.';
    }
}
