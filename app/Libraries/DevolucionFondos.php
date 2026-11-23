<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\RendicionModel;
use App\Models\UsuarioModel;

/**
 * Cuando la rendición cerró debiendo plata, la empresa puede devolver ese monto
 * con el mismo archivo de nómina del banco.
 */
class DevolucionFondos
{
    /**
     * @param array<string, mixed> $rendicion
     */
    public function puede(array $rendicion): bool
    {
        if ((string) ($rendicion['estado'] ?? '') !== 'cerrada') {
            return false;
        }
        if ((string) ($rendicion['reembolso'] ?? '') === 'devuelto') {
            return false;
        }
        $cuenta = (new SaldoRendicion())->de($rendicion);

        return $cuenta['saldo'] < -0.009;
    }

    /**
     * @param array<string, mixed> $empresa
     * @param array<string, mixed> $rendicion
     *
     * @return array{ok: bool, errores: list<string>, contenido: string, nombre: string}
     */
    public function devolver(array $empresa, array $rendicion): array
    {
        if (! $this->puede($rendicion)) {
            return ['ok' => false, 'errores' => ['Esta rendición no está esperando la devolución de fondos.'], 'contenido' => '', 'nombre' => ''];
        }
        if ((string) ($empresa['pais'] ?? '') !== 'CL') {
            model(RendicionModel::class)->update($rendicion['id'], ['reembolso' => 'devuelto']);

            return ['ok' => true, 'errores' => [], 'contenido' => '', 'nombre' => ''];
        }
        $persona = model(UsuarioModel::class)->find((int) $rendicion['usuario_id']);
        if (! is_array($persona)) {
            return ['ok' => false, 'errores' => ['No encontramos a la persona de la rendición.'], 'contenido' => '', 'nombre' => ''];
        }
        $cuenta = (new SaldoRendicion())->de($rendicion);
        $archivo = NominaBci::texto([
            'anio'         => (int) date('Y'),
            'mes'          => (int) date('n'),
            'cuenta_cargo' => (string) ($empresa['cuenta_cargo'] ?? ''),
            'lineas'       => [[
                'nombre'        => (string) ($persona['nombre'] ?? ''),
                'cuenta_banco'  => (string) ($persona['cuenta_banco'] ?? ''),
                'banco_destino' => (string) ($persona['banco_destino'] ?? ''),
                'rut'           => (string) ($persona['id_fiscal'] ?? ''),
                'monto'         => round(abs($cuenta['saldo']), 2),
                'motivo'        => 'Devolucion de fondos',
                'usuario_id'    => (int) $persona['id'],
            ]],
        ], [(int) $persona['id'] => (string) ($persona['email'] ?? '')]);
        if (! $archivo['ok']) {
            return $archivo;
        }
        model(RendicionModel::class)->update($rendicion['id'], ['reembolso' => 'devuelto']);
        $archivo['nombre'] = 'devolucion-' . (int) $rendicion['id'] . '.txt';

        return $archivo;
    }
}
