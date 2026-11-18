<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\SolicitudModel;
use App\Models\UsuarioModel;

class NominaFondos
{
    /**
     * @return array{ok: bool, errores: list<string>, id?: int}
     */
    public function cerrar(int $empresaId, int $anio, int $mes): array
    {
        if ($mes < 1 || $mes > 12) {
            return ['ok' => false, 'errores' => ['Elige un mes.']];
        }
        $pendientes = $this->pendientes($empresaId, $anio, $mes);
        if ($pendientes === []) {
            return ['ok' => false, 'errores' => ['No hay solicitudes aceptadas de este mes para cerrar en la nómina.']];
        }
        $personas = [];
        foreach (model(UsuarioModel::class)->where('empresa_id', $empresaId)->findAll() as $persona) {
            $personas[(int) $persona['id']] = $persona;
        }
        $db = db_connect();
        $db->transException(true)->transStart();
        $db->table('nominas')->insert([
            'empresa_id' => $empresaId,
            'anio'       => $anio,
            'mes'        => $mes,
            'estado'     => 'cerrada',
            'erp_ref'    => '',
            'creada'     => date('Y-m-d H:i:s'),
            'consumida'  => null,
        ]);
        $id = (int) $db->insertID();
        foreach ($pendientes as $solicitud) {
            $persona = $personas[(int) $solicitud['usuario_id']] ?? null;
            $db->table('nomina_lineas')->insert([
                'nomina_id'    => $id,
                'solicitud_id' => (int) $solicitud['id'],
                'usuario_id'   => (int) $solicitud['usuario_id'],
                'nombre'       => is_array($persona) ? (string) $persona['nombre'] : '',
                'rut'          => is_array($persona) ? (string) ($persona['id_fiscal'] ?? '') : '',
                'motivo'       => (string) $solicitud['motivo'],
                'monto'        => $solicitud['monto'],
                'moneda'       => (string) $solicitud['moneda'],
            ]);
        }
        $db->transComplete();

        return ['ok' => true, 'errores' => [], 'id' => $id];
    }

    /**
     * @return array{ok: bool, errores: list<string>, nomina?: array<string, mixed>}
     */
    public function consumir(int $empresaId, int $id, string $referencia): array
    {
        $referencia = trim($referencia);
        if ($referencia === '' || strlen($referencia) > 120) {
            return ['ok' => false, 'errores' => ['Escribe la referencia con la que el ERP guardó la nómina.']];
        }
        $nomina = $this->una($empresaId, $id);
        if ($nomina === null) {
            return ['ok' => false, 'errores' => ['No encontramos esa nómina.']];
        }
        if ((string) $nomina['estado'] === 'consumida') {
            if ((string) $nomina['erp_ref'] === $referencia) {
                return ['ok' => true, 'errores' => [], 'nomina' => $nomina];
            }

            return ['ok' => false, 'errores' => ['Esa nómina ya fue tomada por el ERP.']];
        }
        db_connect()->table('nominas')->where('id', $id)->update([
            'estado'    => 'consumida',
            'erp_ref'   => $referencia,
            'consumida' => date('Y-m-d H:i:s'),
        ]);
        $tomada = $this->una($empresaId, $id);

        return is_array($tomada)
            ? ['ok' => true, 'errores' => [], 'nomina' => $tomada]
            : ['ok' => false, 'errores' => ['No encontramos esa nómina.']];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendientes(int $empresaId, int $anio, int $mes): array
    {
        [$desde, $hasta] = $this->limites($anio, $mes);
        $ocupadas = array_map(
            static fn (array $fila): int => (int) $fila['solicitud_id'],
            db_connect()->table('nomina_lineas')
                ->select('nomina_lineas.solicitud_id')
                ->join('nominas', 'nominas.id = nomina_lineas.nomina_id')
                ->where('nominas.empresa_id', $empresaId)
                ->get()
                ->getResultArray(),
        );
        $items = model(SolicitudModel::class)
            ->where('empresa_id', $empresaId)
            ->where('estado', 'aceptada')
            ->where('creada >=', $desde)
            ->where('creada <', $hasta)
            ->orderBy('id', 'ASC')
            ->findAll();

        return array_values(array_filter(
            $items,
            static fn (array $item): bool => ! in_array((int) $item['id'], $ocupadas, true),
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function cerradas(int $empresaId): array
    {
        $nominas = db_connect()->table('nominas')
            ->where('empresa_id', $empresaId)
            ->where('estado', 'cerrada')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
        foreach ($nominas as &$nomina) {
            $completa = $this->una($empresaId, (int) $nomina['id']);
            if (is_array($completa)) {
                $nomina = $completa;
            }
        }
        unset($nomina);

        return $nominas;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function delMes(int $empresaId, int $anio, int $mes, ?string $estado = null): array
    {
        $consulta = db_connect()->table('nominas')
            ->where('empresa_id', $empresaId)
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->orderBy('id', 'ASC');
        if ($estado !== null) {
            $consulta->where('estado', $estado);
        }
        $nominas = $consulta->get()->getResultArray();
        foreach ($nominas as &$nomina) {
            $nomina['lineas'] = db_connect()->table('nomina_lineas')
                ->where('nomina_id', $nomina['id'])
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();
        }
        unset($nomina);

        return $nominas;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function una(int $empresaId, int $id): ?array
    {
        $nomina = db_connect()->table('nominas')
            ->where('empresa_id', $empresaId)
            ->where('id', $id)
            ->get()
            ->getRowArray();
        if (! is_array($nomina)) {
            return null;
        }
        $nomina['lineas'] = db_connect()->table('nomina_lineas')
            ->where('nomina_id', $id)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        return $nomina;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function limites(int $anio, int $mes): array
    {
        $desde = sprintf('%04d-%02d-01 00:00:00', $anio, $mes);
        $hasta = date('Y-m-d 00:00:00', strtotime($desde . ' +1 month'));

        return [$desde, $hasta];
    }
}
