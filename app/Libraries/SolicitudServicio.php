<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\AceptadorModel;
use App\Models\CategoriaModel;
use App\Models\FondoModel;
use App\Models\SolicitudModel;

class SolicitudServicio
{
    /**
     * @param array<string, mixed> $usuario
     * @param array<string, mixed> $empresa
     *
     * @return array{ok: bool, errores: list<string>, solicitud?: array<string, mixed>}
     */
    public function pedir(array $usuario, array $empresa, string $motivo, string $montoTexto, int $categoriaId): array
    {
        if (! Planes::esRendidor($usuario)) {
            return ['ok' => false, 'errores' => [Planes::SIN_MARCA]];
        }
        $motivo = trim($motivo);
        $monto  = Montos::parse($montoTexto);
        $categoria = model(CategoriaModel::class)->find($categoriaId);
        if ($motivo === '' || strlen($motivo) > 180) {
            return ['ok' => false, 'errores' => ['Escribe el motivo del fondo.']];
        }
        if ($monto === null || $monto <= 0) {
            return ['ok' => false, 'errores' => ['El monto tiene que ser mayor que cero.']];
        }
        if (! is_array($categoria) || (int) $categoria['empresa_id'] !== (int) $empresa['id'] || (int) $categoria['activa'] !== 1) {
            return ['ok' => false, 'errores' => ['Elige un gasto activo de la empresa.']];
        }

        $ahora    = date('Y-m-d H:i:s');
        $detencion = $this->detencion($usuario, $empresa, $categoriaId, $monto);
        $cola     = $detencion === '' ? $this->cola((int) $empresa['id']) : [];
        $primero  = $cola[0] ?? null;
        $estado   = 'aceptada';
        if ($detencion !== '') {
            $estado = 'detenida';
        } elseif (is_array($primero)) {
            $estado = 'en_aceptacion';
        }
        $id = model(SolicitudModel::class)->insert([
            'empresa_id'       => (int) $empresa['id'],
            'usuario_id'       => (int) $usuario['id'],
            'categoria_id'     => $categoriaId,
            'motivo'           => $motivo,
            'monto'            => $monto,
            'moneda'           => (string) (Paises::pais((string) $empresa['pais'])['moneda'] ?? 'USD'),
            'estado'           => $estado,
            'detalle'          => $detencion,
            'turno_usuario_id' => is_array($primero) ? (int) $primero['usuario_id'] : null,
            'turno_orden'      => is_array($primero) ? (int) $primero['orden'] : 0,
            'creada'           => $ahora,
            'resuelta'         => $estado === 'en_aceptacion' ? null : $ahora,
            'entregada'        => null,
        ], true);
        $solicitud = model(SolicitudModel::class)->find($id);

        return is_array($solicitud)
            ? ['ok' => true, 'errores' => [], 'solicitud' => $solicitud]
            : ['ok' => false, 'errores' => ['No pudimos guardar la solicitud.']];
    }

    /**
     * @param array<string, mixed> $solicitud
     * @param array<string, mixed> $actor
     *
     * @return array{ok: bool, errores: list<string>, solicitud?: array<string, mixed>}
     */
    public function aceptar(array $solicitud, array $actor): array
    {
        if ((string) $solicitud['estado'] !== 'en_aceptacion' || (int) $solicitud['turno_usuario_id'] !== (int) $actor['id']) {
            return ['ok' => false, 'errores' => ['Esta solicitud no está en tu turno.']];
        }
        $siguiente = $this->siguiente($solicitud);
        if ($siguiente === false) {
            return ['ok' => false, 'errores' => ['El flujo cambió. La solicitud espera a que se vuelva a armar el orden.']];
        }
        $ahora = date('Y-m-d H:i:s');
        db_connect()->table('solicitud_pasos')->insert([
            'solicitud_id' => $solicitud['id'],
            'usuario_id'   => $actor['id'],
            'orden'        => $solicitud['turno_orden'],
            'decision'     => 'aceptada',
            'detalle'      => '',
            'creada'       => $ahora,
        ]);
        $datos = is_array($siguiente)
            ? [
                'estado'           => 'en_aceptacion',
                'turno_usuario_id' => (int) $siguiente['usuario_id'],
                'turno_orden'      => (int) $siguiente['orden'],
                'resuelta'         => null,
            ]
            : [
                'estado'           => 'aceptada',
                'turno_usuario_id' => null,
                'turno_orden'      => 0,
                'detalle'          => '',
                'resuelta'         => $ahora,
            ];
        model(SolicitudModel::class)->update($solicitud['id'], $datos);

        return $this->leida((int) $solicitud['id']);
    }

    /**
     * @param array<string, mixed> $solicitud
     * @param array<string, mixed> $actor
     *
     * @return array{ok: bool, errores: list<string>, solicitud?: array<string, mixed>}
     */
    public function rechazar(array $solicitud, array $actor, string $detalle): array
    {
        if ((string) $solicitud['estado'] !== 'en_aceptacion' || (int) $solicitud['turno_usuario_id'] !== (int) $actor['id']) {
            return ['ok' => false, 'errores' => ['Esta solicitud no está en tu turno.']];
        }
        $detalle = mb_substr(trim($detalle), 0, 180);
        if ($detalle === '') {
            $detalle = 'Rechazada en el flujo de aceptación.';
        }
        $ahora = date('Y-m-d H:i:s');
        db_connect()->table('solicitud_pasos')->insert([
            'solicitud_id' => $solicitud['id'],
            'usuario_id'   => $actor['id'],
            'orden'        => $solicitud['turno_orden'],
            'decision'     => 'rechazada',
            'detalle'      => $detalle,
            'creada'       => $ahora,
        ]);
        model(SolicitudModel::class)->update($solicitud['id'], [
            'estado'           => 'rechazada',
            'detalle'          => $detalle,
            'turno_usuario_id' => null,
            'turno_orden'      => 0,
            'resuelta'         => $ahora,
        ]);

        return $this->leida((int) $solicitud['id']);
    }

    /**
     * @param array<string, mixed> $solicitud
     * @param array<string, mixed> $actor
     *
     * @return array{ok: bool, errores: list<string>, solicitud?: array<string, mixed>}
     */
    public function entregar(array $solicitud, array $actor): array
    {
        if (($actor['rol'] ?? '') !== 'administrador') {
            return ['ok' => false, 'errores' => ['Tesorería entrega el fondo.']];
        }
        if ((string) $solicitud['estado'] !== 'aceptada') {
            return ['ok' => false, 'errores' => ['El fondo se entrega cuando la solicitud está aceptada.']];
        }
        $ahora = date('Y-m-d H:i:s');
        $db    = db_connect();
        $db->transException(true)->transStart();
        model(FondoModel::class)->insert([
            'empresa_id'   => $solicitud['empresa_id'],
            'usuario_id'   => $solicitud['usuario_id'],
            'solicitud_id' => $solicitud['id'],
            'motivo'       => $solicitud['motivo'],
            'monto'        => $solicitud['monto'],
            'saldo'        => $solicitud['monto'],
            'moneda'       => $solicitud['moneda'],
            'estado'       => 'abierto',
            'creado'       => $ahora,
        ]);
        model(SolicitudModel::class)->update($solicitud['id'], [
            'estado'    => 'entregada',
            'entregada' => $ahora,
        ]);
        $db->transComplete();

        return $this->leida((int) $solicitud['id']);
    }

    /**
     * @param array<string, mixed> $usuario
     * @param array<string, mixed> $empresa
     */
    private function detencion(array $usuario, array $empresa, int $categoriaId, float $monto): string
    {
        $maximo = (float) ($empresa['monto_maximo_rendicion'] ?? 0);
        if ((int) ($empresa['detener_monto'] ?? 1) === 1 && $maximo > 0 && $monto > $maximo) {
            return 'Supera el monto máximo de una rendición.';
        }
        if ((int) ($empresa['detener_tipo'] ?? 1) === 1 && ! ParametrosBase::aceptado((int) $empresa['id'], (int) $usuario['id'], $categoriaId)) {
            return 'Ese gasto no está permitido para esta persona.';
        }
        if ((int) ($empresa['detener_habilitacion'] ?? 1) === 1 && (int) $usuario['activo'] !== 1) {
            return 'La persona no está habilitada.';
        }

        return '';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function cola(int $empresaId): array
    {
        return model(AceptadorModel::class)
            ->where('empresa_id', $empresaId)
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * @param array<string, mixed> $solicitud
     *
     * @return array<string, mixed>|false|null
     */
    private function siguiente(array $solicitud): array|false|null
    {
        $pasado = false;
        foreach ($this->cola((int) $solicitud['empresa_id']) as $paso) {
            if (! $pasado) {
                if ((int) $paso['usuario_id'] === (int) $solicitud['turno_usuario_id'] && (int) $paso['orden'] === (int) $solicitud['turno_orden']) {
                    $pasado = true;
                }
                continue;
            }

            return $paso;
        }

        return $pasado ? null : false;
    }

    /**
     * @return array{ok: bool, errores: list<string>, solicitud?: array<string, mixed>}
     */
    private function leida(int $id): array
    {
        $solicitud = model(SolicitudModel::class)->find($id);

        return is_array($solicitud)
            ? ['ok' => true, 'errores' => [], 'solicitud' => $solicitud]
            : ['ok' => false, 'errores' => ['No encontramos la solicitud.']];
    }
}
