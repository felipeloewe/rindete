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
            return ['ok' => false, 'errores' => ['No hay solicitudes aceptadas de este mes para la nómina de fondos por rendir.']];
        }
        $personas = [];
        foreach (model(UsuarioModel::class)->where('empresa_id', $empresaId)->findAll() as $persona) {
            $personas[(int) $persona['id']] = $persona;
        }
        $errores = [];
        foreach ($pendientes as $solicitud) {
            $persona = $personas[(int) $solicitud['usuario_id']] ?? null;
            $nombre = is_array($persona) ? (string) $persona['nombre'] : 'Una persona';
            if (! is_array($persona) || trim((string) ($persona['id_fiscal'] ?? '')) === '') {
                $errores[] = 'A ' . $nombre . ' le falta el RUT.';
            }
            if (! is_array($persona) || trim((string) ($persona['cuenta_banco'] ?? '')) === '') {
                $errores[] = 'A ' . $nombre . ' le falta la cuenta del banco. Se anota en Personas.';
            }
        }
        if ($errores !== []) {
            return ['ok' => false, 'errores' => $errores];
        }
        $ahora = date('Y-m-d H:i:s');
        $db = db_connect();
        $db->transException(true)->transStart();
        $db->table('nominas')->insert([
            'empresa_id' => $empresaId,
            'anio'       => $anio,
            'mes'        => $mes,
            'estado'     => 'en_banco',
            'erp_ref'    => '',
            'creada'     => $ahora,
            'consumida'  => null,
        ]);
        $id = (int) $db->insertID();
        foreach ($pendientes as $solicitud) {
            $persona = $personas[(int) $solicitud['usuario_id']];
            $db->table('nomina_lineas')->insert([
                'nomina_id'    => $id,
                'solicitud_id' => (int) $solicitud['id'],
                'usuario_id'   => (int) $solicitud['usuario_id'],
                'nombre'       => (string) $persona['nombre'],
                'rut'          => (string) $persona['id_fiscal'],
                'cuenta_banco' => (string) $persona['cuenta_banco'],
                'motivo'       => (string) $solicitud['motivo'],
                'monto'        => $solicitud['monto'],
                'moneda'       => (string) $solicitud['moneda'],
            ]);
            $db->table('fondos')->insert([
                'empresa_id'   => $empresaId,
                'usuario_id'   => (int) $solicitud['usuario_id'],
                'solicitud_id' => (int) $solicitud['id'],
                'motivo'       => (string) $solicitud['motivo'],
                'monto'        => $solicitud['monto'],
                'saldo'        => $solicitud['monto'],
                'moneda'       => (string) $solicitud['moneda'],
                'estado'       => 'abierto',
                'creado'       => $ahora,
            ]);
            $db->table('solicitudes')->where('id', (int) $solicitud['id'])->update([
                'estado'    => 'entregada',
                'entregada' => $ahora,
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
        if ((string) $nomina['estado'] === 'confirmada') {
            if ((string) $nomina['erp_ref'] === $referencia) {
                return ['ok' => true, 'errores' => [], 'nomina' => $nomina];
            }

            return ['ok' => false, 'errores' => ['Esa nómina ya fue registrada por el banco.']];
        }
        if (! in_array((string) $nomina['estado'], ['en_banco', 'cerrada'], true)) {
            return ['ok' => false, 'errores' => ['La nómina se registra cuando ya está creada en el banco.']];
        }
        db_connect()->table('nominas')->where('id', $id)->update([
            'estado'    => 'confirmada',
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
            ->whereIn('estado', ['en_banco', 'cerrada'])
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
        $nomina['banco'] = $this->bancoDeLaEmpresa($empresaId);
        $nomina['lineas'] = db_connect()->table('nomina_lineas')
            ->where('nomina_id', $id)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        return $nomina;
    }

    private function bancoDeLaEmpresa(int $empresaId): string
    {
        $empresa = db_connect()->table('empresas')->select('preasiento_salida_id')->where('id', $empresaId)->get()->getRowArray();
        $cuentaId = (int) ($empresa['preasiento_salida_id'] ?? 0);
        if ($cuentaId < 1) {
            return '';
        }
        $cuenta = db_connect()->table('cuentas')->select('codigo')->where('id', $cuentaId)->get()->getRowArray();

        return is_array($cuenta) ? (string) $cuenta['codigo'] : '';
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
