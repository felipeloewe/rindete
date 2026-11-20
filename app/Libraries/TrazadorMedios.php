<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\CategoriaModel;
use App\Models\TarjetaModel;
use App\Models\UsuarioModel;

class TrazadorMedios
{
    /** @var array<string, string> */
    private const MACH = [
        'business-payment-completed' => 'Pago de comercio completado',
        'business-payment-expired'   => 'Pago de comercio expirado',
        'business-payment-failed'    => 'Pago de comercio fallido',
        'business-payment-reversed'  => 'Pago de comercio reversado',
        'business-refund-completed'  => 'Devolución de comercio completada',
        'business-refund-failed'     => 'Devolución de comercio fallida',
        'compensation-started'       => 'Compensación iniciada',
    ];

    /**
     * @param array<string, mixed> $cuerpo
     */
    public function global66(int $empresaId, array $cuerpo): void
    {
        $data = $cuerpo['data'] ?? null;
        if (is_array($data) && isset($data['transactionId'])) {
            $this->abonoGlobal($empresaId, $data);
        }
        $payload = $cuerpo['payload'] ?? null;
        if (is_array($payload) && isset($payload['transactionId'])) {
            $this->remesaGlobal($empresaId, $payload);
        }
    }

    /**
     * @param array<string, mixed> $cuerpo
     */
    public function mach(int $empresaId, array $cuerpo): void
    {
        $evento = (string) ($cuerpo['event_name'] ?? '');
        if ($evento === '' || ! isset(self::MACH[$evento])) {
            return;
        }
        $recurso = (string) ($cuerpo['event_resource_id'] ?? $cuerpo['resource_id'] ?? $cuerpo['event_upstream_id'] ?? '');
        if ($recurso === '') {
            return;
        }
        $devolucion = str_starts_with($evento, 'business-refund');
        $this->anotar($empresaId, 'mach', 'mach-' . $recurso, null, null, $devolucion ? 'cargo' : 'abono', 0.0, 'CLP', self::MACH[$evento], 'comercio', date('Y-m-d H:i:s'));
    }

    /**
     * @param list<array<string, mixed>> $movements
     */
    public function incorporar(int $empresaId, int $tarjetaId, array $movements): int
    {
        $tarjeta = $this->tarjeta($empresaId, $tarjetaId);
        if ($tarjeta === null || (string) $tarjeta['emisor'] !== 'global66') {
            return 0;
        }
        $nuevos = 0;
        foreach ($movements as $movimiento) {
            if (! is_array($movimiento) || ! isset($movimiento['id'])) {
                continue;
            }
            $tipo = strtoupper((string) ($movimiento['type'] ?? ''));
            $clase = strtoupper((string) ($movimiento['typeMovement'] ?? ''));
            $categoria = strtoupper((string) ($movimiento['category'] ?? ''));
            $sentido = ($clase === 'DEPOSIT' || $categoria === 'PAY_IN') ? 'abono' : 'cargo';
            $comision = str_contains($tipo, 'FEE') || $tipo === 'GMF_FEE';
            $cambio = $tipo === 'EXCHANGE';
            $pagado = strtoupper((string) ($movimiento['status'] ?? '')) === 'PAID';
            $estado = 'trazado';
            if ($sentido === 'cargo' && $pagado && ! $comision && ! $cambio && (int) $tarjeta['exclusiva'] === 1) {
                $estado = 'por_rendir';
            }
            $monto = abs((float) ($movimiento['amount'] ?? 0));
            if ($estado === 'por_rendir' && $monto <= 0) {
                $estado = 'trazado';
            }
            $detalle = trim((string) ($movimiento['comment'] ?? $movimiento['nameCommerce'] ?? $movimiento['type'] ?? 'Movimiento'));
            $antes = $this->existe($empresaId, 'global66', 'g66-mov-' . $movimiento['id']);
            $this->anotar(
                $empresaId,
                'global66',
                'g66-mov-' . $movimiento['id'],
                (int) $tarjeta['id'],
                $tarjeta['titular_id'] !== null ? (int) $tarjeta['titular_id'] : null,
                $sentido,
                $monto,
                $this->moneda((string) ($movimiento['currencyCode'] ?? 'CLP')),
                $detalle === '' ? 'Movimiento' : $detalle,
                $estado,
                $this->fecha((string) ($movimiento['date'] ?? '')),
            );
            if ($antes === null) {
                $nuevos++;
            }
        }

        return $nuevos;
    }

    /**
     * @return array{filas: list<array<string, mixed>>, categorias: list<array<string, mixed>>}
     */
    public function estado(int $empresaId, int $anio, int $mes, ?int $usuarioId): array
    {
        [$desde, $hasta] = InformeServicio::limites($anio, $mes);
        $consulta = db_connect()->table('movimientos_medio')
            ->select('movimientos_medio.*, tarjetas.nombre AS tarjeta, tarjetas.ultimos4, tarjetas.exclusiva, usuarios.nombre AS persona')
            ->join('tarjetas', 'tarjetas.id = movimientos_medio.tarjeta_id', 'left')
            ->join('usuarios', 'usuarios.id = movimientos_medio.usuario_id', 'left')
            ->where('movimientos_medio.empresa_id', $empresaId)
            ->where('ocurrido >=', $desde . ' 00:00:00')
            ->where('ocurrido <', $hasta . ' 00:00:00')
            ->orderBy('ocurrido', 'DESC');
        if ($usuarioId !== null) {
            $consulta->where('movimientos_medio.usuario_id', $usuarioId);
        }

        return [
            'filas'      => $consulta->get()->getResultArray(),
            'categorias' => model(CategoriaModel::class)->where('empresa_id', $empresaId)->orderBy('nombre', 'ASC')->findAll(),
        ];
    }

    /**
     * @param array<string, mixed> $usuario
     *
     * @return array{ok: bool, errores: list<string>, aviso: string}
     */
    public function rendir(array $usuario, int $empresaId, int $movimientoId, int $categoriaId): array
    {
        $fila = db_connect()->table('movimientos_medio')->where('empresa_id', $empresaId)->where('id', $movimientoId)->get()->getRowArray();
        if (! is_array($fila) || (string) $fila['estado'] !== 'por_rendir' || (string) $fila['sentido'] !== 'cargo') {
            return ['ok' => false, 'errores' => ['Ese movimiento no está pendiente de rendir.'], 'aviso' => ''];
        }
        $tarjeta = $this->tarjeta($empresaId, (int) $fila['tarjeta_id']);
        if ($tarjeta === null || (int) $tarjeta['exclusiva'] !== 1) {
            return ['ok' => false, 'errores' => ['La tarjeta de la empresa es la que entra sola a la rendición.'], 'aviso' => ''];
        }
        $titularId = (int) ($fila['usuario_id'] ?? 0);
        $esAdmin = ($usuario['rol'] ?? '') === 'administrador';
        if (! $esAdmin && (int) $usuario['id'] !== $titularId) {
            return ['ok' => false, 'errores' => ['Este movimiento lo rinde la persona de la tarjeta.'], 'aviso' => ''];
        }
        $titular = model(UsuarioModel::class)->find($titularId);
        if (! is_array($titular) || (int) $titular['empresa_id'] !== $empresaId) {
            return ['ok' => false, 'errores' => ['Este movimiento lo rinde la persona de la tarjeta.'], 'aviso' => ''];
        }
        $motivo = trim((string) $fila['detalle']);
        $cargo = (new CargoTarjeta())->crear(
            $titular,
            $empresaId,
            (int) $tarjeta['id'],
            $categoriaId,
            $motivo !== '' ? $motivo : 'Movimiento de la tarjeta',
            (float) $fila['monto'],
            $this->moneda((string) $fila['moneda']),
            false,
        );
        if (! $cargo['ok']) {
            return ['ok' => false, 'errores' => $cargo['errores'], 'aviso' => ''];
        }
        db_connect()->table('movimientos_medio')->where('id', $movimientoId)->update([
            'estado'   => 'en_rendicion',
            'cargo_id' => $cargo['id'] ?? null,
        ]);
        if (($cargo['estado'] ?? '') === 'observado') {
            return ['ok' => true, 'errores' => [], 'aviso' => 'El movimiento quedó observado. No calza con la mantención de la tarjeta.'];
        }

        return ['ok' => true, 'errores' => [], 'aviso' => ''];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function abonoGlobal(int $empresaId, array $data): void
    {
        $tarjeta = $this->porReferencia($empresaId, 'global66', (string) ($data['customerId'] ?? ''));
        $detalle = trim((string) ($data['thirdPartyClientName'] ?? 'Abono'));
        $estadoPago = (string) ($data['status'] ?? '');
        if (strtoupper($estadoPago) === 'REJECTED') {
            $detalle = 'Abono rechazado';
        }
        $this->anotar(
            $empresaId,
            'global66',
            'g66-abono-' . $data['transactionId'],
            $tarjeta === null ? null : (int) $tarjeta['id'],
            $tarjeta === null || $tarjeta['titular_id'] === null ? null : (int) $tarjeta['titular_id'],
            'abono',
            (float) ($data['originAmount'] ?? 0),
            $this->moneda((string) ($data['originCurrencyCode'] ?? 'CLP')),
            $detalle === '' ? 'Abono' : $detalle,
            'trazado',
            date('Y-m-d H:i:s'),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function remesaGlobal(int $empresaId, array $payload): void
    {
        $tarjeta = $this->porReferencia($empresaId, 'global66', (string) ($payload['customerId'] ?? ''));
        $status = strtolower((string) ($payload['status'] ?? ''));
        $monto = (float) ($payload['destinyAmount'] ?? $payload['originAmount'] ?? 0);
        $moneda = (string) ($payload['destinyCurrencyCode'] ?? $payload['originCurrencyCode'] ?? 'CLP');
        $detalle = trim((string) ($payload['purpose'] ?? 'Remesa'));
        $estado = 'trazado';
        if ($status === 'rejected') {
            $detalle = 'Remesa rechazada';
        } elseif ($status === 'successful' && $tarjeta !== null && (int) $tarjeta['exclusiva'] === 1 && $monto > 0) {
            $estado = 'por_rendir';
        }
        $this->anotar(
            $empresaId,
            'global66',
            'g66-remesa-' . $payload['transactionId'],
            $tarjeta === null ? null : (int) $tarjeta['id'],
            $tarjeta === null || $tarjeta['titular_id'] === null ? null : (int) $tarjeta['titular_id'],
            'cargo',
            $monto,
            $this->moneda($moneda),
            $detalle === '' ? 'Remesa' : $detalle,
            $estado,
            $this->fecha((string) ($payload['createdAt'] ?? '')),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function porReferencia(int $empresaId, string $emisor, string $referencia): ?array
    {
        $referencia = trim($referencia);
        if ($referencia === '') {
            return null;
        }
        $fila = model(TarjetaModel::class)
            ->where('empresa_id', $empresaId)
            ->where('emisor', $emisor)
            ->where('referencia', $referencia)
            ->where('activa', 1)
            ->first();

        return is_array($fila) ? $fila : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tarjeta(int $empresaId, int $tarjetaId): ?array
    {
        $fila = model(TarjetaModel::class)->find($tarjetaId);

        return is_array($fila) && (int) $fila['empresa_id'] === $empresaId ? $fila : null;
    }

    private function anotar(
        int $empresaId,
        string $proveedor,
        string $externoId,
        ?int $tarjetaId,
        ?int $usuarioId,
        string $sentido,
        float $monto,
        string $moneda,
        string $detalle,
        string $estado,
        string $ocurrido,
    ): void {
        $actual = $this->existe($empresaId, $proveedor, $externoId);
        $datos = [
            'tarjeta_id' => $tarjetaId,
            'usuario_id' => $usuarioId,
            'sentido'    => $sentido,
            'monto'      => $monto,
            'moneda'     => $moneda,
            'detalle'    => mb_substr($detalle, 0, 180),
            'estado'     => $estado,
        ];
        if ($actual === null) {
            db_connect()->table('movimientos_medio')->insert($datos + [
                'empresa_id' => $empresaId,
                'proveedor'  => $proveedor,
                'externo_id' => mb_substr($externoId, 0, 80),
                'cargo_id'   => null,
                'ocurrido'   => $ocurrido,
            ]);

            return;
        }
        if (in_array((string) $actual['estado'], ['en_rendicion', 'comercio'], true)) {
            return;
        }
        if ((string) $actual['estado'] === 'por_rendir' && $estado !== 'por_rendir') {
            return;
        }
        db_connect()->table('movimientos_medio')->where('id', $actual['id'])->update($datos);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function existe(int $empresaId, string $proveedor, string $externoId): ?array
    {
        $fila = db_connect()->table('movimientos_medio')
            ->where('empresa_id', $empresaId)
            ->where('proveedor', $proveedor)
            ->where('externo_id', $externoId)
            ->get()
            ->getRowArray();

        return is_array($fila) ? $fila : null;
    }

    private function moneda(string $codigo): string
    {
        $codigo = strtoupper(substr(trim($codigo), 0, 3));

        return strlen($codigo) === 3 ? $codigo : 'CLP';
    }

    private function fecha(string $valor): string
    {
        $marca = strtotime($valor);

        return $marca === false ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', $marca);
    }
}
