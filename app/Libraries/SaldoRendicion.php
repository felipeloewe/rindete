<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\AceptadorModel;
use App\Models\FondoModel;
use App\Models\GastoModel;
use App\Models\RendicionModel;
use App\Models\UsuarioModel;

/**
 * El saldo es lo que hay que rendir menos los gastos de la rendición.
 * Negativo pide el cierre. Positivo cierra esta y abre otra con el sobrante.
 * Cero cierra cuadrada.
 */
class SaldoRendicion
{
    /**
     * @param array<string, mixed> $rendicion
     *
     * @return array{por_rendir: float, rendido: float, saldo: float}
     */
    public function de(array $rendicion): array
    {
        $rendido = 0.0;
        foreach (model(GastoModel::class)->where('rendicion_id', $rendicion['id'])->findAll() as $gasto) {
            $rendido += (float) $gasto['monto'];
        }
        $porRendir = round((float) ($rendicion['monto_por_rendir'] ?? 0), 2);
        $rendido = round($rendido, 2);

        return [
            'por_rendir' => $porRendir,
            'rendido'    => $rendido,
            'saldo'      => round($porRendir - $rendido, 2),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fondosLibres(int $empresaId, int $usuarioId): array
    {
        $usados = [];
        foreach (model(RendicionModel::class)->where('empresa_id', $empresaId)->where('fondo_id >', 0)->findAll() as $rendicion) {
            $usados[(int) $rendicion['fondo_id']] = true;
        }
        $libres = [];
        foreach (model(FondoModel::class)->where('empresa_id', $empresaId)->where('usuario_id', $usuarioId)->where('estado', 'abierto')->orderBy('id', 'ASC')->findAll() as $fondo) {
            if (! isset($usados[(int) $fondo['id']])) {
                $libres[] = $fondo;
            }
        }

        return $libres;
    }

    /**
     * @param array<string, mixed> $usuario
     *
     * @return array{ok: bool, errores: list<string>, fondo?: array<string, mixed>}
     */
    public function fondoDe(array $usuario, int $fondoId): array
    {
        if ($fondoId < 1) {
            return ['ok' => true, 'errores' => []];
        }
        $fondo = model(FondoModel::class)->find($fondoId);
        if (! is_array($fondo) || (int) $fondo['empresa_id'] !== (int) $usuario['empresa_id'] || (int) $fondo['usuario_id'] !== (int) $usuario['id'] || (string) $fondo['estado'] !== 'abierto') {
            return ['ok' => false, 'errores' => ['Elige un fondo abierto tuyo.']];
        }
        $tomado = model(RendicionModel::class)->where('fondo_id', $fondoId)->first();
        if (is_array($tomado)) {
            return ['ok' => false, 'errores' => ['Ese fondo ya está en una rendición.']];
        }

        return ['ok' => true, 'errores' => [], 'fondo' => $fondo];
    }

    /**
     * @param array<string, mixed> $rendicion
     *
     * @return array{ok: bool, errores: list<string>, caso?: string, nueva?: int, saldo?: float}
     */
    public function cerrar(array $rendicion, string $adicionalTexto = ''): array
    {
        if ((string) $rendicion['estado'] !== 'abierta') {
            return ['ok' => false, 'errores' => ['Esta rendición ya se cerró o espera aceptación.']];
        }
        $cuenta = $this->de($rendicion);
        if ($cuenta['rendido'] <= 0 && $cuenta['por_rendir'] <= 0) {
            return ['ok' => false, 'errores' => ['Agrega al menos un gasto legalizado.']];
        }
        $adicional = 0.0;
        if (trim($adicionalTexto) !== '') {
            $parsed = Montos::parse($adicionalTexto);
            if ($parsed === null || $parsed < 0) {
                return ['ok' => false, 'errores' => ['El monto adicional tiene que ser un monto.']];
            }
            $adicional = $parsed;
        }
        if ($adicional > 0 && $cuenta['saldo'] <= 0) {
            return ['ok' => false, 'errores' => ['El monto adicional se suma cuando sobra saldo.']];
        }

        $ahora = date('Y-m-d H:i:s');
        if ($cuenta['saldo'] < -0.009) {
            $turno = $this->primerTurno((int) $rendicion['empresa_id']);
            if ($turno === null) {
                return ['ok' => false, 'errores' => ['Falta un administrador para aceptar el cierre.']];
            }
            model(RendicionModel::class)->update($rendicion['id'], [
                'estado'           => 'cierre_pedido',
                'turno_usuario_id' => (int) $turno['usuario_id'],
                'turno_orden'      => (int) $turno['orden'],
            ]);

            return ['ok' => true, 'errores' => [], 'caso' => 'negativo', 'saldo' => $cuenta['saldo']];
        }

        $db = db_connect();
        $db->transException(true)->transStart();
        model(RendicionModel::class)->update($rendicion['id'], [
            'estado'           => 'cerrada',
            'enviada'          => $ahora,
            'turno_usuario_id' => null,
            'turno_orden'      => 0,
        ]);
        $nueva = 0;
        if ($cuenta['saldo'] > 0.009) {
            $titulo = 'Saldo de ' . (string) $rendicion['titulo'];
            $nueva = (int) model(RendicionModel::class)->insert([
                'empresa_id'           => (int) $rendicion['empresa_id'],
                'usuario_id'           => (int) $rendicion['usuario_id'],
                'titulo'               => mb_substr($titulo, 0, 140),
                'nota'                 => 'Nace con el saldo que sobró.',
                'estado'               => 'abierta',
                'creada'               => $ahora,
                'enviada'              => null,
                'origen'               => 'traspaso',
                'monto_maximo'         => $rendicion['monto_maximo'] ?? null,
                'cuenta_viatico_id'    => $rendicion['cuenta_viatico_id'] ?? null,
                'cuenta_asignacion_id' => $rendicion['cuenta_asignacion_id'] ?? null,
                'rendicion_previa_id'  => (int) $rendicion['id'],
                'monto_por_rendir'     => round($cuenta['saldo'] + $adicional, 2),
                'monto_adicional'      => $adicional,
            ], true);
        }
        $db->transComplete();

        return [
            'ok'     => true,
            'errores'=> [],
            'caso'   => $nueva > 0 ? 'positivo' : 'cero',
            'nueva'  => $nueva,
            'saldo'  => $cuenta['saldo'],
        ];
    }

    /**
     * @param array<string, mixed> $rendicion
     * @param array<string, mixed> $actor
     *
     * @return array{ok: bool, errores: list<string>}
     */
    public function aceptar(array $rendicion, array $actor): array
    {
        if ((string) $rendicion['estado'] !== 'cierre_pedido' || (int) ($rendicion['turno_usuario_id'] ?? 0) !== (int) $actor['id']) {
            return ['ok' => false, 'errores' => ['Este cierre no está en tu turno.']];
        }
        $siguiente = $this->siguiente($rendicion);
        if ($siguiente === false) {
            return ['ok' => false, 'errores' => ['El flujo cambió. El cierre espera a que se vuelva a armar el orden.']];
        }
        $ahora = date('Y-m-d H:i:s');
        db_connect()->table('rendicion_pasos')->insert([
            'rendicion_id' => $rendicion['id'],
            'usuario_id'   => $actor['id'],
            'orden'        => (int) ($rendicion['turno_orden'] ?? 0),
            'decision'     => 'aceptada',
            'detalle'      => '',
            'creada'       => $ahora,
        ]);
        if (is_array($siguiente)) {
            model(RendicionModel::class)->update($rendicion['id'], [
                'turno_usuario_id' => (int) $siguiente['usuario_id'],
                'turno_orden'      => (int) $siguiente['orden'],
            ]);
        } else {
            $cambios = [
                'estado'           => 'cerrada',
                'enviada'          => $ahora,
                'turno_usuario_id' => null,
                'turno_orden'      => 0,
            ];
            if ($this->de($rendicion)['saldo'] < -0.009) {
                $cambios['reembolso'] = 'por_devolver';
            }
            model(RendicionModel::class)->update($rendicion['id'], $cambios);
        }

        return ['ok' => true, 'errores' => []];
    }

    /**
     * @param array<string, mixed> $rendicion
     * @param array<string, mixed> $actor
     *
     * @return array{ok: bool, errores: list<string>}
     */
    public function rechazar(array $rendicion, array $actor, string $detalle): array
    {
        if ((string) $rendicion['estado'] !== 'cierre_pedido' || (int) ($rendicion['turno_usuario_id'] ?? 0) !== (int) $actor['id']) {
            return ['ok' => false, 'errores' => ['Este cierre no está en tu turno.']];
        }
        $detalle = mb_substr(trim($detalle), 0, 180);
        if ($detalle === '') {
            $detalle = 'Cierre rechazado.';
        }
        $ahora = date('Y-m-d H:i:s');
        db_connect()->table('rendicion_pasos')->insert([
            'rendicion_id' => $rendicion['id'],
            'usuario_id'   => $actor['id'],
            'orden'        => (int) ($rendicion['turno_orden'] ?? 0),
            'decision'     => 'rechazada',
            'detalle'      => $detalle,
            'creada'       => $ahora,
        ]);
        model(RendicionModel::class)->update($rendicion['id'], [
            'estado'           => 'abierta',
            'turno_usuario_id' => null,
            'turno_orden'      => 0,
        ]);

        return ['ok' => true, 'errores' => []];
    }

    /**
     * @param array<string, mixed> $rendicion
     *
     * @return array{ok: bool, errores: list<string>}
     */
    public function sumar(array $rendicion, string $texto): array
    {
        if ((string) $rendicion['estado'] !== 'abierta') {
            return ['ok' => false, 'errores' => ['El monto adicional se suma mientras la rendición está abierta.']];
        }
        $monto = Montos::parse($texto);
        if ($monto === null || $monto <= 0) {
            return ['ok' => false, 'errores' => ['El monto adicional tiene que ser mayor que cero.']];
        }
        model(RendicionModel::class)->update($rendicion['id'], [
            'monto_por_rendir' => round((float) ($rendicion['monto_por_rendir'] ?? 0) + $monto, 2),
            'monto_adicional'  => round((float) ($rendicion['monto_adicional'] ?? 0) + $monto, 2),
        ]);

        return ['ok' => true, 'errores' => []];
    }

    public function porRendirAbierto(int $empresaId, ?int $usuarioId): float
    {
        $consulta = model(RendicionModel::class)
            ->where('empresa_id', $empresaId)
            ->whereIn('estado', ['abierta', 'cierre_pedido']);
        if ($usuarioId !== null) {
            $consulta->where('usuario_id', $usuarioId);
        }
        $total = 0.0;
        foreach ($consulta->findAll() as $rendicion) {
            $saldo = $this->de($rendicion)['saldo'];
            if ($saldo > 0) {
                $total += $saldo;
            }
        }

        return round($total, 2);
    }

    /**
     * @return array{usuario_id: int, orden: int}|null
     */
    private function primerTurno(int $empresaId): ?array
    {
        $primero = model(AceptadorModel::class)->where('empresa_id', $empresaId)->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->first();
        if (is_array($primero)) {
            return ['usuario_id' => (int) $primero['usuario_id'], 'orden' => (int) $primero['orden']];
        }
        $admin = model(UsuarioModel::class)
            ->where('empresa_id', $empresaId)
            ->where('rol', 'administrador')
            ->where('activo', 1)
            ->orderBy('id', 'ASC')
            ->first();
        if (! is_array($admin)) {
            return null;
        }

        return ['usuario_id' => (int) $admin['id'], 'orden' => 0];
    }

    /**
     * @param array<string, mixed> $rendicion
     *
     * @return array<string, mixed>|false|null
     */
    private function siguiente(array $rendicion): array|false|null
    {
        if ((int) ($rendicion['turno_orden'] ?? 0) === 0) {
            return null;
        }
        $pasado = false;
        foreach (model(AceptadorModel::class)->where('empresa_id', $rendicion['empresa_id'])->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll() as $paso) {
            if (! $pasado) {
                if ((int) $paso['usuario_id'] === (int) $rendicion['turno_usuario_id'] && (int) $paso['orden'] === (int) $rendicion['turno_orden']) {
                    $pasado = true;
                }
                continue;
            }

            return $paso;
        }

        return $pasado ? null : false;
    }
}
