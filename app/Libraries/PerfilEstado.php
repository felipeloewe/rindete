<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\FondoModel;
use App\Models\GastoModel;
use App\Models\RendicionModel;
use App\Models\SolicitudModel;

/**
 * Lo que la persona ve en Mi perfil: el trabajo pendiente, los fondos
 * y rendiciones abiertos, y las alertas de lo solicitado o lo objetado.
 */
class PerfilEstado
{
    /**
     * @param array<string, mixed> $usuario
     *
     * @return array{
     *     trabajo: list<array{texto: string, href: string}>,
     *     fondos: list<array<string, mixed>>,
     *     rendiciones: list<array<string, mixed>>,
     *     alertas: list<array{texto: string, href: string}>
     * }
     */
    public function de(array $usuario): array
    {
        $empresaId = (int) $usuario['empresa_id'];
        $usuarioId = (int) $usuario['id'];
        $gastos = model(GastoModel::class)
            ->where('empresa_id', $empresaId)
            ->where('usuario_id', $usuarioId)
            ->where('estado', 'observado')
            ->orderBy('id', 'DESC')
            ->findAll();
        $rendiciones = model(RendicionModel::class)
            ->where('empresa_id', $empresaId)
            ->where('usuario_id', $usuarioId)
            ->where('estado', 'abierta')
            ->orderBy('id', 'DESC')
            ->findAll();
        $objetadas = $this->objetadas($rendiciones);
        $solicitudes = model(SolicitudModel::class)
            ->where('empresa_id', $empresaId)
            ->where('usuario_id', $usuarioId)
            ->where('estado', 'en_aceptacion')
            ->orderBy('id', 'DESC')
            ->findAll();
        $turnos = model(SolicitudModel::class)
            ->where('empresa_id', $empresaId)
            ->where('estado', 'en_aceptacion')
            ->where('turno_usuario_id', $usuarioId)
            ->orderBy('id', 'DESC')
            ->findAll();
        $cierres = model(RendicionModel::class)
            ->where('empresa_id', $empresaId)
            ->where('estado', 'cierre_pedido')
            ->where('turno_usuario_id', $usuarioId)
            ->orderBy('id', 'DESC')
            ->findAll();

        return [
            'trabajo'     => $this->trabajo($usuario, $gastos, $rendiciones, $objetadas, $turnos, $cierres),
            'fondos'      => model(FondoModel::class)
                ->where('empresa_id', $empresaId)
                ->where('usuario_id', $usuarioId)
                ->where('estado', 'abierto')
                ->orderBy('id', 'DESC')
                ->findAll(),
            'rendiciones' => $rendiciones,
            'alertas'     => $this->alertas($gastos, $rendiciones, $objetadas, $solicitudes),
        ];
    }

    /**
     * @param array<string, mixed>                  $usuario
     * @param list<array<string, mixed>>            $gastos
     * @param list<array<string, mixed>>            $rendiciones
     * @param array<int, true>                      $objetadas
     * @param list<array<string, mixed>>            $turnos
     * @param list<array<string, mixed>>            $cierres
     *
     * @return list<array{texto: string, href: string}>
     */
    private function trabajo(array $usuario, array $gastos, array $rendiciones, array $objetadas, array $turnos, array $cierres): array
    {
        $items = [];
        foreach ((new Recepcion())->pendientes($usuario) as $entrada) {
            $items[] = [
                'texto' => 'Confirma la boleta que llegó.',
                'href'  => 'gastos/entradas/' . (int) $entrada['id'],
            ];
        }
        foreach ($gastos as $gasto) {
            $items[] = [
                'texto' => 'Corrige el gasto ' . (string) $gasto['concepto'] . '.',
                'href'  => 'gastos/' . (int) $gasto['id'],
            ];
        }
        foreach ($cierres as $rendicion) {
            $items[] = [
                'texto' => 'Acepta el cierre de ' . (string) $rendicion['titulo'] . '.',
                'href'  => 'rendiciones/' . (int) $rendicion['id'],
            ];
        }
        foreach ($turnos as $solicitud) {
            $items[] = [
                'texto' => 'Responde la solicitud ' . (string) $solicitud['motivo'] . '.',
                'href'  => 'fondos/' . (int) $solicitud['id'],
            ];
        }
        foreach ($rendiciones as $rendicion) {
            if (! isset($objetadas[(int) $rendicion['id']])) {
                continue;
            }
            $items[] = [
                'texto' => 'Retoma la rendición ' . (string) $rendicion['titulo'] . '.',
                'href'  => 'rendiciones/' . (int) $rendicion['id'],
            ];
        }

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $gastos
     * @param list<array<string, mixed>> $rendiciones
     * @param array<int, true>           $objetadas
     * @param list<array<string, mixed>> $solicitudes
     *
     * @return list<array{texto: string, href: string}>
     */
    private function alertas(array $gastos, array $rendiciones, array $objetadas, array $solicitudes): array
    {
        $items = [];
        foreach ($solicitudes as $solicitud) {
            $items[] = [
                'texto' => 'Solicitaste el fondo ' . (string) $solicitud['motivo'] . '.',
                'href'  => 'fondos/' . (int) $solicitud['id'],
            ];
        }
        foreach ($gastos as $gasto) {
            $items[] = [
                'texto' => 'El gasto ' . (string) $gasto['concepto'] . ' está objetado.',
                'href'  => 'gastos/' . (int) $gasto['id'],
            ];
        }
        foreach ($rendiciones as $rendicion) {
            if (! isset($objetadas[(int) $rendicion['id']])) {
                continue;
            }
            $items[] = [
                'texto' => 'La rendición ' . (string) $rendicion['titulo'] . ' fue objetada.',
                'href'  => 'rendiciones/' . (int) $rendicion['id'],
            ];
        }

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $rendiciones
     *
     * @return array<int, true>
     */
    private function objetadas(array $rendiciones): array
    {
        $ids = array_map(static fn (array $fila): int => (int) $fila['id'], $rendiciones);
        if ($ids === []) {
            return [];
        }
        $marcas = [];
        foreach (db_connect()->table('rendicion_pasos')->whereIn('rendicion_id', $ids)->where('decision', 'rechazada')->get()->getResultArray() as $paso) {
            $marcas[(int) $paso['rendicion_id']] = true;
        }

        return $marcas;
    }
}
