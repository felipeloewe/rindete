<?php

declare(strict_types=1);

namespace App\Libraries;

class PreAsiento
{
    /**
     * @param array<string, mixed> $gasto
     */
    public static function sincronizar(array $gasto): void
    {
        $db       = db_connect();
        $gastoId  = (int) $gasto['id'];
        $cuentaId = ParametrosBase::cuentaDeCategoria((int) $gasto['categoria_id']);
        $listo    = in_array((string) $gasto['estado'], ['legalizado', 'en_rendicion', 'aprobado'], true);
        $tabla    = $db->table('pre_asientos');
        if (! $listo || $cuentaId < 1) {
            $tabla->where('gasto_id', $gastoId)->where('estado', 'propuesto')->delete();

            return;
        }
        $datos = [
            'empresa_id' => (int) $gasto['empresa_id'],
            'cuenta_id'  => $cuentaId,
            'monto'      => $gasto['monto'],
            'moneda'     => (string) $gasto['moneda'],
            'estado'     => 'propuesto',
        ];
        $existente = $tabla->where('gasto_id', $gastoId)->get()->getRowArray();
        if (is_array($existente)) {
            if ((string) $existente['estado'] === 'propuesto') {
                $db->table('pre_asientos')->where('id', $existente['id'])->update($datos);
            }

            return;
        }
        $datos['gasto_id'] = $gastoId;
        $datos['creado']   = date('Y-m-d H:i:s');
        $db->table('pre_asientos')->insert($datos);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function deGasto(int $gastoId): ?array
    {
        $fila = db_connect()->table('pre_asientos')
            ->select('pre_asientos.*, cuentas.codigo, cuentas.nombre')
            ->join('cuentas', 'cuentas.id = pre_asientos.cuenta_id')
            ->where('pre_asientos.gasto_id', $gastoId)
            ->get()
            ->getRowArray();

        return is_array($fila) ? $fila : null;
    }
}
