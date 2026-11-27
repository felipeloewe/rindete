<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Centros de costo, sucursales y otros conceptos que la carga ofrece.
 */
class Lugares
{
    /** @var array<string, string> */
    public const TIPOS = [
        'centro'   => 'centros',
        'sucursal' => 'sucursales',
        'concepto' => 'conceptos_extra',
    ];

    /** @var array<string, string> */
    public const ETIQUETAS = [
        'centro'   => 'Centro de costo',
        'sucursal' => 'Sucursal',
        'concepto' => 'Otro concepto',
    ];

    /** @var array<string, string> */
    public const USO = [
        'centro'   => 'usa_centro',
        'sucursal' => 'usa_sucursal',
        'concepto' => 'usa_concepto',
    ];

    /**
     * @param array<string, mixed> $empresa
     */
    public function dePago(array $empresa): bool
    {
        return (string) ($empresa['plan'] ?? '') === 'cobro';
    }

    /**
     * @param array<string, mixed> $empresa
     */
    public function ofrece(array $empresa, string $tipo): bool
    {
        $campo = self::USO[$tipo] ?? '';

        return $campo !== '' && $this->dePago($empresa) && (int) ($empresa[$campo] ?? 0) === 1;
    }

    public function marcar(int $empresaId, string $tipo, bool $habilitado): bool
    {
        $campo = self::USO[$tipo] ?? '';
        if ($campo === '') {
            return false;
        }

        return model(\App\Models\EmpresaModel::class)->update($empresaId, [
            $campo => $habilitado ? 1 : 0,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function lista(int $empresaId, string $tipo, bool $soloActivos = false): array
    {
        $tabla = self::TIPOS[$tipo] ?? '';
        if ($tabla === '') {
            return [];
        }
        $consulta = db_connect()->table($tabla)->where('empresa_id', $empresaId)->orderBy('nombre', 'ASC');
        if ($soloActivos) {
            $consulta->where('activo', 1);
        }

        return $consulta->get()->getResultArray();
    }

    /**
     * @return array{ok: bool, errores: list<string>}
     */
    public function crear(int $empresaId, string $tipo, string $nombre): array
    {
        $tabla = self::TIPOS[$tipo] ?? '';
        $nombre = mb_substr(trim($nombre), 0, 80);
        if ($tabla === '' || $nombre === '') {
            return ['ok' => false, 'errores' => ['Escribe el nombre.']];
        }
        $existe = db_connect()->table($tabla)->where('empresa_id', $empresaId)->where('nombre', $nombre)->countAllResults();
        if ($existe > 0) {
            return ['ok' => false, 'errores' => ['Ese nombre ya está.']];
        }
        db_connect()->table($tabla)->insert([
            'empresa_id' => $empresaId,
            'nombre'     => $nombre,
            'activo'     => 1,
        ]);

        return ['ok' => true, 'errores' => []];
    }

    public function quitar(int $empresaId, string $tipo, int $id): bool
    {
        $tabla = self::TIPOS[$tipo] ?? '';
        if ($tabla === '' || $id < 1) {
            return false;
        }

        return db_connect()->table($tabla)->where('empresa_id', $empresaId)->where('id', $id)->delete() !== false
            && db_connect()->affectedRows() > 0;
    }

    public function nombre(int $empresaId, string $tipo, int $id): string
    {
        if ($id < 1) {
            return '';
        }
        foreach ($this->lista($empresaId, $tipo) as $fila) {
            if ((int) $fila['id'] === $id) {
                return (string) $fila['nombre'];
            }
        }

        return '';
    }

    public function activo(int $empresaId, string $tipo, int $id): bool
    {
        $tabla = self::TIPOS[$tipo] ?? '';
        if ($tabla === '' || $id < 1) {
            return false;
        }
        $fila = db_connect()->table($tabla)->where('empresa_id', $empresaId)->where('id', $id)->where('activo', 1)->get()->getRowArray();

        return is_array($fila);
    }
}
