<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\CategoriaModel;
use App\Models\FondoModel;
use App\Models\RendicionModel;
use App\Models\SolicitudModel;

/**
 * Abre un fondo por rendir y su rendición sin pasar por la solicitud de tesorería.
 * El respaldo queda en estado interna para no entrar a la planilla ni al tablero de pagos.
 */
class FondoAbierto
{
    /**
     * @return array{ok: bool, errores: list<string>, fondo_id?: int, rendicion_id?: int}
     */
    public function abrir(int $empresaId, int $usuarioId, int $categoriaId, string $motivo, float $monto, string $moneda, string $origen, string $titulo): array
    {
        $categoria = model(CategoriaModel::class)->find($categoriaId);
        if (! is_array($categoria) || (int) $categoria['empresa_id'] !== $empresaId) {
            return ['ok' => false, 'errores' => ['Elige una categoría de la empresa.']];
        }
        $motivo = mb_substr(trim($motivo), 0, 140);
        if ($motivo === '' || $monto <= 0) {
            return ['ok' => false, 'errores' => ['Escribe el motivo y un monto mayor que cero.']];
        }
        $ahora = date('Y-m-d H:i:s');
        $solicitud = (int) model(SolicitudModel::class)->insert([
            'empresa_id'   => $empresaId,
            'usuario_id'   => $usuarioId,
            'categoria_id' => $categoriaId,
            'motivo'       => $motivo,
            'monto'        => $monto,
            'moneda'       => $moneda,
            'estado'       => 'interna',
            'detalle'      => '',
            'turno_orden'  => 0,
            'creada'       => $ahora,
            'resuelta'     => $ahora,
            'entregada'    => $ahora,
        ], true);
        $fondo = (int) model(FondoModel::class)->insert([
            'empresa_id'   => $empresaId,
            'usuario_id'   => $usuarioId,
            'solicitud_id' => $solicitud,
            'motivo'       => $motivo,
            'monto'        => $monto,
            'saldo'        => $monto,
            'moneda'       => $moneda,
            'estado'       => 'abierto',
            'origen'       => $origen,
            'creado'       => $ahora,
        ], true);
        $rendicion = (int) model(RendicionModel::class)->insert([
            'empresa_id'       => $empresaId,
            'usuario_id'       => $usuarioId,
            'titulo'           => mb_substr($titulo, 0, 140),
            'nota'             => '',
            'estado'           => 'abierta',
            'creada'           => $ahora,
            'enviada'          => null,
            'origen'           => $origen,
            'fondo_id'         => $fondo,
            'monto_por_rendir' => $monto,
            'monto_maximo'     => 0,
        ], true);

        return ['ok' => true, 'errores' => [], 'fondo_id' => $fondo, 'rendicion_id' => $rendicion];
    }
}
