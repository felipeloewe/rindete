<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\GastoModel;
use App\Models\RendicionModel;

class RendicionServicio
{
    /**
     * @param array<string, mixed> $usuario
     *
     * @return array{ok: bool, errores: list<string>, id?: int}
     */
    public function crear(array $usuario, string $titulo, string $nota): array
    {
        $titulo = trim($titulo);
        $nota   = trim($nota);
        if ($titulo === '' || strlen($titulo) > 140) {
            return ['ok' => false, 'errores' => ['Escribe un nombre para la rendición.']];
        }
        if (strlen($nota) > 500) {
            return ['ok' => false, 'errores' => ['La nota es demasiado larga.']];
        }

        $id = model(RendicionModel::class)->insert([
            'empresa_id' => (int) $usuario['empresa_id'],
            'usuario_id' => (int) $usuario['id'],
            'titulo'     => $titulo,
            'nota'       => $nota,
            'estado'     => 'abierta',
            'creada'     => date('Y-m-d H:i:s'),
            'enviada'    => null,
        ], true);

        return ['ok' => true, 'errores' => [], 'id' => (int) $id];
    }

    /**
     * @param array<string, mixed> $rendicion
     * @param array<string, mixed> $gasto
     *
     * @return array{ok: bool, errores: list<string>}
     */
    public function agregar(array $rendicion, array $gasto): array
    {
        if ((string) $rendicion['estado'] !== 'abierta') {
            return ['ok' => false, 'errores' => ['Esta rendición ya se envió.']];
        }
        if ((int) $gasto['empresa_id'] !== (int) $rendicion['empresa_id'] || (int) $gasto['usuario_id'] !== (int) $rendicion['usuario_id']) {
            return ['ok' => false, 'errores' => ['Ese gasto no pertenece a quien arma la rendición.']];
        }
        if ((string) $gasto['estado'] !== 'legalizado' || (int) ($gasto['rendicion_id'] ?? 0) !== 0) {
            return ['ok' => false, 'errores' => ['Solo puedes sumar gastos legalizados.']];
        }

        model(GastoModel::class)->update($gasto['id'], [
            'rendicion_id' => (int) $rendicion['id'],
            'estado'       => 'en_rendicion',
        ]);

        return ['ok' => true, 'errores' => []];
    }

    /**
     * @param array<string, mixed> $rendicion
     * @param array<string, mixed> $gasto
     *
     * @return array{ok: bool, errores: list<string>}
     */
    public function quitar(array $rendicion, array $gasto): array
    {
        if ((string) $rendicion['estado'] !== 'abierta') {
            return ['ok' => false, 'errores' => ['Esta rendición ya se envió.']];
        }
        if ((int) ($gasto['rendicion_id'] ?? 0) !== (int) $rendicion['id']) {
            return ['ok' => false, 'errores' => ['Ese gasto no está en esta rendición.']];
        }

        model(GastoModel::class)->update($gasto['id'], [
            'rendicion_id' => null,
            'estado'       => 'legalizado',
        ]);
        (new GastoServicio())->reevaluar((int) $rendicion['empresa_id']);

        return ['ok' => true, 'errores' => []];
    }

    /**
     * @param array<string, mixed> $rendicion
     *
     * @return array{ok: bool, errores: list<string>}
     */
    public function enviar(array $rendicion): array
    {
        if ((string) $rendicion['estado'] !== 'abierta') {
            return ['ok' => false, 'errores' => ['Esta rendición ya se envió.']];
        }
        $cantidad = model(GastoModel::class)->where('rendicion_id', $rendicion['id'])->countAllResults();
        if ($cantidad < 1) {
            return ['ok' => false, 'errores' => ['Agrega al menos un gasto legalizado.']];
        }

        model(RendicionModel::class)->update($rendicion['id'], [
            'estado'  => 'enviada',
            'enviada' => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'errores' => []];
    }

    /**
     * @param array<string, mixed> $rendicion
     *
     * @return array{ok: bool, errores: list<string>}
     */
    public function eliminar(array $rendicion): array
    {
        if ((string) $rendicion['estado'] !== 'abierta') {
            return ['ok' => false, 'errores' => ['Una rendición enviada no se puede eliminar.']];
        }

        $gastos = model(GastoModel::class)->where('rendicion_id', $rendicion['id'])->findAll();
        foreach ($gastos as $gasto) {
            model(GastoModel::class)->update($gasto['id'], [
                'rendicion_id' => null,
                'estado'       => 'legalizado',
            ]);
        }
        model(RendicionModel::class)->delete($rendicion['id']);
        (new GastoServicio())->reevaluar((int) $rendicion['empresa_id']);

        return ['ok' => true, 'errores' => []];
    }
}
