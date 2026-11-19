<?php

declare(strict_types=1);

namespace App\Libraries;

class FormasPago
{
    public function asegurar(int $empresaId): void
    {
        if (db_connect()->table('formas_pago')->where('empresa_id', $empresaId)->countAllResults() > 0) {
            return;
        }
        $db = db_connect()->table('formas_pago');
        $db->insert(['empresa_id' => $empresaId, 'nombre' => 'Efectivo', 'efectivo' => 1, 'activo' => 1]);
        $db->insert(['empresa_id' => $empresaId, 'nombre' => 'Transferencia', 'efectivo' => 0, 'activo' => 1]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function lista(int $empresaId, bool $soloActivas = false): array
    {
        $this->asegurar($empresaId);
        $consulta = db_connect()->table('formas_pago')->where('empresa_id', $empresaId)->orderBy('nombre', 'ASC');
        if ($soloActivas) {
            $consulta->where('activo', 1);
        }

        return $consulta->get()->getResultArray();
    }

    /**
     * @return array{ok: bool, errores: list<string>}
     */
    public function crear(int $empresaId, string $nombre, bool $efectivo): array
    {
        $nombre = mb_substr(trim($nombre), 0, 80);
        if ($nombre === '') {
            return ['ok' => false, 'errores' => ['Escribe la forma de pago.']];
        }
        $this->asegurar($empresaId);
        $existe = db_connect()->table('formas_pago')->where('empresa_id', $empresaId)->where('nombre', $nombre)->countAllResults();
        if ($existe > 0) {
            return ['ok' => false, 'errores' => ['Esa forma de pago ya está.']];
        }
        db_connect()->table('formas_pago')->insert([
            'empresa_id' => $empresaId,
            'nombre'     => $nombre,
            'efectivo'   => $efectivo ? 1 : 0,
            'activo'     => 1,
        ]);

        return ['ok' => true, 'errores' => []];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function una(int $empresaId, int $id): ?array
    {
        $fila = db_connect()->table('formas_pago')->where('empresa_id', $empresaId)->where('id', $id)->get()->getRowArray();

        return is_array($fila) ? $fila : null;
    }
}
