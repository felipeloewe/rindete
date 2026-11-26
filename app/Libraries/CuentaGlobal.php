<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\FondoModel;
use App\Models\TarjetaModel;

/**
 * Una cuenta Global66 por persona que rinde. La empresa tiene varias.
 * El abono confirma que la nómina de esa persona llegó a su cuenta.
 */
class CuentaGlobal
{
    /**
     * @param array<string, mixed> $input
     *
     * @return array{ok: bool, errores: list<string>, aplica: bool, cliente: string, billetera: string}
     */
    public function revisar(int $empresaId, int $usuarioId, array $input): array
    {
        $vacio = ['ok' => true, 'errores' => [], 'aplica' => false, 'cliente' => '', 'billetera' => ''];
        if (! array_key_exists('global_cliente', $input) && ! array_key_exists('global_billetera', $input)) {
            return $vacio;
        }
        $cliente = trim((string) ($input['global_cliente'] ?? ''));
        $billetera = trim((string) ($input['global_billetera'] ?? ''));
        if ($cliente === '' && $billetera === '') {
            return $vacio;
        }
        if ($billetera === '') {
            return ['ok' => false, 'errores' => ['La billetera es el accountID de la cuenta.'], 'aplica' => false, 'cliente' => '', 'billetera' => ''];
        }
        if ($cliente !== '' && (preg_match('/^\d+$/', $cliente) !== 1 || strlen($cliente) > 80)) {
            return ['ok' => false, 'errores' => ['El identificador de la cuenta Global66 es numérico.'], 'aplica' => false, 'cliente' => '', 'billetera' => ''];
        }
        if (preg_match('/^\d+$/', $billetera) !== 1 || strlen($billetera) > 40) {
            return ['ok' => false, 'errores' => ['La billetera Global66 es numérica.'], 'aplica' => false, 'cliente' => '', 'billetera' => ''];
        }
        $propia = $this->de($empresaId, $usuarioId);
        $propiaId = is_array($propia) ? (int) $propia['id'] : 0;
        if ($cliente !== '' && $this->ocupada($empresaId, 'referencia', $cliente, $propiaId)) {
            return ['ok' => false, 'errores' => ['Esa cuenta Global66 ya es de otra persona.'], 'aplica' => false, 'cliente' => '', 'billetera' => ''];
        }
        if ($this->ocupada($empresaId, 'billetera', $billetera, $propiaId)) {
            return ['ok' => false, 'errores' => ['La billetera Global66 ya es de otra persona.'], 'aplica' => false, 'cliente' => '', 'billetera' => ''];
        }

        return ['ok' => true, 'errores' => [], 'aplica' => true, 'cliente' => $cliente, 'billetera' => $billetera];
    }

    public function asignar(int $empresaId, int $usuarioId, string $cliente, string $billetera): void
    {
        $actual = $this->de($empresaId, $usuarioId);
        $datos = [
            'nombre'      => 'Cuenta Global66',
            'referencia'  => $cliente,
            'billetera'   => $billetera,
            'titular_id'  => $usuarioId,
            'exclusiva'   => 1,
            'personal'    => 0,
            'activa'      => 1,
            'emisor'      => 'global66',
        ];
        if (is_array($actual)) {
            model(TarjetaModel::class)->update($actual['id'], $datos);
            $tarjetaId = (int) $actual['id'];
        } else {
            $tarjetaId = (int) model(TarjetaModel::class)->insert($datos + [
                'empresa_id'     => $empresaId,
                'ultimos4'       => '',
                'monto_aceptado' => 0,
            ], true);
        }
        $enlace = db_connect()->table('tarjeta_usuarios')
            ->where('tarjeta_id', $tarjetaId)
            ->where('usuario_id', $usuarioId)
            ->get()
            ->getRowArray();
        if (! is_array($enlace)) {
            db_connect()->table('tarjeta_usuarios')->insert([
                'tarjeta_id' => $tarjetaId,
                'usuario_id' => $usuarioId,
            ]);
        }
    }

    /**
     * El abono calza con un fondo que la nómina ya abrió para esta persona.
     */
    public function confirmarLlegada(int $empresaId, int $usuarioId, float $monto, string $moneda): bool
    {
        $moneda = strtoupper(substr(trim($moneda), 0, 3));
        $fondos = model(FondoModel::class)
            ->where('empresa_id', $empresaId)
            ->where('usuario_id', $usuarioId)
            ->where('estado', 'abierto')
            ->where('en_cuenta', 0)
            ->orderBy('id', 'ASC')
            ->findAll();
        foreach ($fondos as $fondo) {
            $origen = (string) ($fondo['origen'] ?? '');
            if (in_array($origen, ['tarjeta', 'caja'], true)) {
                continue;
            }
            $solicitudId = (int) ($fondo['solicitud_id'] ?? 0);
            if ($solicitudId < 1 || strtoupper((string) $fondo['moneda']) !== $moneda) {
                continue;
            }
            if (abs((float) $fondo['monto'] - $monto) >= 0.009) {
                continue;
            }
            $linea = db_connect()->table('nomina_lineas')->where('solicitud_id', $solicitudId)->get()->getRowArray();
            if (! is_array($linea)) {
                continue;
            }
            model(FondoModel::class)->update($fondo['id'], ['en_cuenta' => 1]);

            return true;
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function delEmpresa(int $empresaId): array
    {
        $cuentas = [];
        foreach (model(TarjetaModel::class)->where('empresa_id', $empresaId)->where('emisor', 'global66')->where('activa', 1)->findAll() as $tarjeta) {
            $titular = (int) ($tarjeta['titular_id'] ?? 0);
            if ($titular > 0) {
                $cuentas[$titular] = $tarjeta;
            }
        }

        return $cuentas;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function de(int $empresaId, int $usuarioId): ?array
    {
        if ($usuarioId < 1) {
            return null;
        }
        $fila = model(TarjetaModel::class)
            ->where('empresa_id', $empresaId)
            ->where('emisor', 'global66')
            ->where('titular_id', $usuarioId)
            ->where('activa', 1)
            ->first();

        return is_array($fila) ? $fila : null;
    }

    private function ocupada(int $empresaId, string $campo, string $valor, int $excepto): bool
    {
        $fila = model(TarjetaModel::class)
            ->where('empresa_id', $empresaId)
            ->where('emisor', 'global66')
            ->where($campo, $valor)
            ->where('activa', 1)
            ->first();

        return is_array($fila) && (int) $fila['id'] !== $excepto;
    }
}
