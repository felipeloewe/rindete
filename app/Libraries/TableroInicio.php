<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\GastoModel;
use App\Models\RendicionModel;

/**
 * En el inicio, cada rendición empuja a cerrarse.
 * Abierta sigue juntando gastos. En proceso ya está rendida y espera el dinero de vuelta.
 * Cerrada recibió esos fondos o terminó el flujo.
 */
class TableroInicio
{
    /**
     * @param array<string, mixed> $usuario
     *
     * @return array{abiertas: list<array<string, mixed>>, proceso: list<array<string, mixed>>, cerradas: list<array<string, mixed>>, duplicados: list<array<string, mixed>>}
     */
    public function armar(array $usuario): array
    {
        $consulta = model(RendicionModel::class)->where('empresa_id', (int) $usuario['empresa_id']);
        if (($usuario['rol'] ?? '') !== 'administrador') {
            $consulta->where('usuario_id', (int) $usuario['id']);
        }
        $saldos = new SaldoRendicion();
        $grupos = ['abierta' => [], 'proceso' => [], 'cerrada' => []];
        foreach ($consulta->orderBy('id', 'DESC')->findAll() as $fila) {
            $cuenta = $saldos->de($fila);
            $grupo = $this->grupo($fila, $cuenta);
            $fila['saldo_calculado'] = $cuenta['saldo'];
            $fila['aviso'] = $this->aviso($fila, $cuenta, $usuario);
            $grupos[$grupo][] = $fila;
        }

        return [
            'abiertas'   => $grupos['abierta'],
            'proceso'    => $grupos['proceso'],
            'cerradas'   => $grupos['cerrada'],
            'duplicados' => $this->duplicados($usuario),
        ];
    }

    /**
     * @param array<string, mixed> $fila
     * @param array{por_rendir: float, rendido: float, saldo: float} $cuenta
     */
    public function grupo(array $fila, array $cuenta): string
    {
        $estado = (string) ($fila['estado'] ?? '');
        if ($estado === 'cerrada') {
            $debe = $cuenta['saldo'] < -0.009 && (string) ($fila['reembolso'] ?? '') !== 'devuelto';

            return $debe ? 'proceso' : 'cerrada';
        }
        if ($estado === 'cierre_pedido') {
            return 'proceso';
        }

        return 'abierta';
    }

    /**
     * @param array<string, mixed> $fila
     * @param array{por_rendir: float, rendido: float, saldo: float} $cuenta
     * @param array<string, mixed> $usuario
     */
    private function aviso(array $fila, array $cuenta, array $usuario): string
    {
        $estado = (string) ($fila['estado'] ?? '');
        if ($estado === 'cierre_pedido') {
            return (int) ($fila['turno_usuario_id'] ?? 0) === (int) $usuario['id']
                ? 'Acepta el cierre para seguir con la devolución de los fondos.'
                : 'Está rendida y espera la aceptación para devolver los fondos.';
        }
        if ($estado === 'cerrada' && $cuenta['saldo'] < -0.009 && (string) ($fila['reembolso'] ?? '') !== 'devuelto') {
            return 'Está rendida y espera los fondos de vuelta.';
        }
        if ($estado === 'cerrada' && (string) ($fila['reembolso'] ?? '') === 'devuelto') {
            return 'Ya recibió los fondos de vuelta.';
        }
        if ($estado === 'cerrada') {
            return 'Cerró el flujo.';
        }

        return 'Sigue abierta. Ciérrala para terminar el flujo.';
    }

    /**
     * @param array<string, mixed> $usuario
     *
     * @return list<array<string, mixed>>
     */
    private function duplicados(array $usuario): array
    {
        $consulta = model(GastoModel::class)
            ->where('empresa_id', (int) $usuario['empresa_id'])
            ->where('duplicado', 1)
            ->where('estado !=', 'aprobado');
        if (($usuario['rol'] ?? '') !== 'administrador') {
            $consulta->where('usuario_id', (int) $usuario['id']);
        }

        return $consulta->orderBy('id', 'DESC')->findAll();
    }
}
