<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\GastoModel;
use App\Models\UsuarioModel;

class CajaChica
{
    /**
     * @return array{ok: bool, errores: list<string>}
     */
    public function entregar(int $empresaId, int $usuarioId, int $categoriaId, string $motivo, float $monto, string $moneda): array
    {
        $persona = model(UsuarioModel::class)->find($usuarioId);
        if (! is_array($persona) || (int) $persona['empresa_id'] !== $empresaId) {
            return ['ok' => false, 'errores' => ['Elige a quién se le entrega.']];
        }
        $abierto = (new FondoAbierto())->abrir(
            $empresaId,
            $usuarioId,
            $categoriaId,
            $motivo,
            $monto,
            $moneda,
            'caja',
            'Caja ' . mb_substr(trim($motivo), 0, 120),
        );
        if (! $abierto['ok']) {
            return $abierto;
        }
        $this->movimiento($empresaId, $usuarioId, 'entrega', $motivo, $monto, $moneda, $abierto['fondo_id'], $abierto['rendicion_id'], null, false);

        return ['ok' => true, 'errores' => []];
    }

    /**
     * @return array{ok: bool, errores: list<string>, avisos: list<string>}
     */
    public function reponer(int $empresaId, string $motivo, float $monto, string $moneda): array
    {
        $motivo = mb_substr(trim($motivo), 0, 180);
        if ($motivo === '' || $monto <= 0) {
            return ['ok' => false, 'errores' => ['Escribe el motivo y un monto mayor que cero.'], 'avisos' => []];
        }
        $gasto = $this->yaRendido($empresaId, $monto);
        $avisos = [];
        if ($gasto !== null) {
            $avisos[] = 'El gasto ' . $gasto['concepto'] . ' ya parece rendido. Queda marcado para revisión de caja.';
        }
        $this->movimiento(
            $empresaId,
            null,
            'reposicion',
            $motivo,
            $monto,
            $moneda,
            null,
            null,
            $gasto === null ? null : (int) $gasto['id'],
            $gasto !== null,
        );

        return ['ok' => true, 'errores' => [], 'avisos' => $avisos];
    }

    public function saldo(int $empresaId): float
    {
        $saldo = 0.0;
        foreach (db_connect()->table('caja_movimientos')->where('empresa_id', $empresaId)->get()->getResultArray() as $fila) {
            $signo = (string) $fila['tipo'] === 'reposicion' ? 1 : -1;
            $saldo += $signo * (float) $fila['monto'];
        }

        return round($saldo, 2);
    }

    /**
     * @return array{filas: list<array<string, mixed>>, saldo: float, entregado: float, reponer: float}
     */
    public function estado(int $empresaId, int $anio, int $mes, ?int $usuarioId): array
    {
        [$desde, $hasta] = InformeServicio::limites($anio, $mes);
        $nombres = [];
        foreach (model(UsuarioModel::class)->where('empresa_id', $empresaId)->findAll() as $persona) {
            $nombres[(int) $persona['id']] = (string) $persona['nombre'];
        }
        $consulta = db_connect()->table('caja_movimientos')
            ->where('empresa_id', $empresaId)
            ->where('creada >=', $desde)
            ->where('creada <', $hasta)
            ->orderBy('id', 'ASC');
        if ($usuarioId !== null) {
            $consulta->groupStart()->where('usuario_id', $usuarioId)->orWhere('tipo', 'reposicion')->groupEnd();
        }
        $filas = [];
        $entregado = 0.0;
        $reponer = 0.0;
        foreach ($consulta->get()->getResultArray() as $fila) {
            $fila['nombre'] = $nombres[(int) ($fila['usuario_id'] ?? 0)] ?? '';
            $filas[] = $fila;
            if ((string) $fila['tipo'] === 'reposicion') {
                $reponer += (float) $fila['monto'];
            } else {
                $entregado += (float) $fila['monto'];
            }
        }

        return [
            'filas'     => $filas,
            'saldo'     => round($reponer - $entregado, 2),
            'entregado' => $entregado,
            'reponer'   => $reponer,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function yaRendido(int $empresaId, float $monto): ?array
    {
        $monto = round($monto, 2);
        foreach (model(GastoModel::class)->where('empresa_id', $empresaId)->where('rendicion_id >', 0)->orderBy('id', 'ASC')->findAll() as $gasto) {
            if (! in_array((string) $gasto['estado'], InformeServicio::RENDIDOS, true)) {
                continue;
            }
            if (round((float) $gasto['monto'], 2) === $monto) {
                return $gasto;
            }
        }

        return null;
    }

    private function movimiento(int $empresaId, ?int $usuarioId, string $tipo, string $motivo, float $monto, string $moneda, ?int $fondoId, ?int $rendicionId, ?int $gastoId, bool $duplicado): void
    {
        db_connect()->table('caja_movimientos')->insert([
            'empresa_id'   => $empresaId,
            'usuario_id'   => $usuarioId,
            'tipo'         => $tipo,
            'motivo'       => mb_substr(trim($motivo), 0, 180),
            'monto'        => $monto,
            'moneda'       => $moneda,
            'fondo_id'     => $fondoId,
            'rendicion_id' => $rendicionId,
            'gasto_id'     => $gastoId,
            'duplicado'    => $duplicado ? 1 : 0,
            'creada'       => date('Y-m-d H:i:s'),
        ]);
    }
}
