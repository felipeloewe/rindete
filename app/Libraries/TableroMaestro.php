<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\EmpresaModel;
use App\Models\UsuarioModel;

class TableroMaestro
{
    /**
     * @return array{
     *     empresas: list<array<string, mixed>>,
     *     activas: int,
     *     planes: list<array{nombre: string, cantidad: int}>,
     *     mensual: list<array{moneda: string, monto: float}>,
     *     anual: list<array{moneda: string, monto: float}>,
     *     personas: list<array{id: int, nombre: string, email: string, empresa: string, papel: string, activo: bool, administrador: bool, rinde: bool}>
     * }
     */
    public function armar(): array
    {
        $admins = [];
        $cupos  = [];
        $personas = [];
        foreach (model(UsuarioModel::class)->orderBy('id', 'ASC')->findAll() as $persona) {
            $empresaId = (int) ($persona['empresa_id'] ?? 0);
            $esAdmin   = (string) ($persona['rol'] ?? '') === 'administrador';
            $rinde     = Planes::esRendidor($persona);
            if ($rinde) {
                $cupos[$empresaId] = ($cupos[$empresaId] ?? 0) + 1;
            }
            $ficha = [
                'id'            => (int) ($persona['id'] ?? 0),
                'nombre'        => (string) ($persona['nombre'] ?? ''),
                'email'         => (string) ($persona['email'] ?? ''),
                'papel'         => $this->papel($esAdmin, $rinde),
                'activo'        => (int) ($persona['activo'] ?? 0) === 1,
                'administrador' => $esAdmin,
                'rinde'         => $rinde,
            ];
            $personas[$empresaId][] = $ficha;
            if ((string) ($persona['rol'] ?? '') === 'administrador') {
                $admins[$empresaId][] = $ficha;
            }
        }

        $filas   = [];
        $activas = 0;
        $planes  = [];
        $mensual = [];
        $anual   = [];
        foreach (model(EmpresaModel::class)->orderBy('id', 'DESC')->findAll() as $empresa) {
            $id       = (int) $empresa['id'];
            $vigente  = (int) ($empresa['vigente'] ?? 1) === 1;
            $esFree   = (string) ($empresa['plan'] ?? 'free') !== 'cobro';
            $nombre   = $esFree ? 'Plan Free' : (trim((string) ($empresa['cobro_plan'] ?? '')) !== '' ? (string) $empresa['cobro_plan'] : 'Plan en cobro');
            $monto    = (float) ($empresa['cobro_monto'] ?? 0);
            $moneda   = (string) (($empresa['cobro_moneda'] ?? '') !== '' ? $empresa['cobro_moneda'] : 'CLP');
            $cada     = (string) ($empresa['cobro_periodicidad'] ?? '');
            $filas[]  = [
                'id'           => $id,
                'nombre'       => (string) ($empresa['nombre'] ?? ''),
                'pais'         => (string) (Paises::pais((string) ($empresa['pais'] ?? ''))['nombre'] ?? $empresa['pais'] ?? ''),
                'id_fiscal'    => (string) ($empresa['id_fiscal'] ?? ''),
                'plan'         => $nombre,
                'es_free'      => $esFree,
                'monto'        => $esFree ? '' : Montos::formato($monto, $moneda),
                'periodicidad' => $esFree ? '' : ($cada === 'anual' ? 'Anual' : 'Mensual'),
                'cupo'         => ($cupos[$id] ?? 0) . ' / ' . (int) ($empresa['cupo_usuarios'] ?? Planes::USUARIOS),
                'admins'       => $admins[$id] ?? [],
                'creado'       => substr((string) ($empresa['creado'] ?? ''), 0, 10),
                'vigente'      => $vigente,
            ];
            if (! $vigente) {
                continue;
            }
            $activas++;
            $planes[$nombre] = ($planes[$nombre] ?? 0) + 1;
            if ($esFree || $monto <= 0) {
                continue;
            }
            if ($cada === 'anual') {
                $anual[$moneda] = ($anual[$moneda] ?? 0) + $monto;
            } else {
                $mensual[$moneda] = ($mensual[$moneda] ?? 0) + $monto;
            }
        }

        if (! array_key_exists('Plan Free', $planes)) {
            $planes = ['Plan Free' => 0] + $planes;
        }
        $cajas = [];
        foreach ($planes as $nombre => $cantidad) {
            $cajas[] = ['nombre' => $nombre, 'cantidad' => $cantidad];
        }

        $lista = [];
        foreach ($filas as $empresa) {
            foreach ($personas[$empresa['id']] ?? [] as $persona) {
                $persona['empresa'] = $empresa['nombre'];
                $lista[] = $persona;
            }
        }

        return [
            'empresas' => $filas,
            'activas'  => $activas,
            'planes'   => $cajas,
            'mensual'  => $this->sumas($mensual),
            'anual'    => $this->sumas($anual),
            'personas' => $lista,
        ];
    }

    private function papel(bool $administrador, bool $rinde): string
    {
        if (! $administrador) {
            return 'Usuario';
        }

        return $rinde ? 'Administrador y usuario' : 'Solo administrador';
    }

    /**
     * @param array<string, float> $montos
     *
     * @return list<array{moneda: string, monto: float}>
     */
    private function sumas(array $montos): array
    {
        if ($montos === []) {
            return [['moneda' => 'CLP', 'monto' => 0.0]];
        }
        $lista = [];
        foreach ($montos as $moneda => $monto) {
            $lista[] = ['moneda' => $moneda, 'monto' => $monto];
        }

        return $lista;
    }
}
