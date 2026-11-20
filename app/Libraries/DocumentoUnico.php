<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\UsuarioModel;
use CodeIgniter\HTTP\Files\UploadedFile;

class DocumentoUnico
{
    /**
     * @return array{ok: bool, errores: list<string>}
     */
    public function crear(int $empresaId, int $usuarioId, int $formaId, string $motivo, float $monto, string $moneda, ?UploadedFile $archivo): array
    {
        $forma = (new FormasPago())->una($empresaId, $formaId);
        if ($forma === null || (int) $forma['activo'] !== 1) {
            return ['ok' => false, 'errores' => ['Elige una forma de pago.']];
        }
        $motivo = mb_substr(trim($motivo), 0, 180);
        if ($motivo === '' || $monto <= 0) {
            return ['ok' => false, 'errores' => ['Escribe el motivo y un monto mayor que cero.']];
        }
        $comprobante = '';
        if ((int) $forma['efectivo'] === 1) {
            $subida = (new Comprobante())->guardar($archivo, $empresaId);
            if (! $subida['ok']) {
                return ['ok' => false, 'errores' => [$subida['error']]];
            }
            if ($subida['nombre'] === '') {
                return ['ok' => false, 'errores' => ['El efectivo exige el comprobante de pago al contado.']];
            }
            $comprobante = $subida['nombre'];
        }
        db_connect()->table('documentos')->insert([
            'empresa_id'    => $empresaId,
            'usuario_id'    => $usuarioId,
            'forma_pago_id' => $formaId,
            'motivo'        => $motivo,
            'monto'         => $monto,
            'moneda'        => $moneda,
            'comprobante'   => $comprobante,
            'estado'        => 'informado',
            'creada'        => date('Y-m-d H:i:s'),
            'resuelta'      => null,
        ]);

        return ['ok' => true, 'errores' => []];
    }

    /**
     * @return array{ok: bool, errores: list<string>}
     */
    public function resolver(int $empresaId, int $id, string $estado): array
    {
        if (! in_array($estado, ['en_pago', 'pagado'], true)) {
            return ['ok' => false, 'errores' => ['Elige si queda en el flujo de pagos o pagado.']];
        }
        $fila = $this->una($empresaId, $id);
        if ($fila === null) {
            return ['ok' => false, 'errores' => ['No encontramos ese documento.']];
        }
        if ((string) $fila['estado'] !== 'informado') {
            return ['ok' => false, 'errores' => ['Tesorería ya procesó ese documento.']];
        }
        db_connect()->table('documentos')->where('id', $id)->update([
            'estado'   => $estado,
            'resuelta' => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'errores' => []];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function lista(int $empresaId): array
    {
        return db_connect()->table('documentos')->where('empresa_id', $empresaId)->orderBy('id', 'DESC')->get()->getResultArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function delMes(int $empresaId, int $anio, int $mes): array
    {
        [$desde, $hasta] = InformeServicio::limites($anio, $mes);
        $nombres = [];
        foreach (model(UsuarioModel::class)->where('empresa_id', $empresaId)->findAll() as $persona) {
            $nombres[(int) $persona['id']] = (string) $persona['nombre'];
        }
        $formas = [];
        foreach ((new FormasPago())->lista($empresaId) as $forma) {
            $formas[(int) $forma['id']] = (string) $forma['nombre'];
        }
        $filas = [];
        $items = db_connect()->table('documentos')
            ->where('empresa_id', $empresaId)
            ->where('creada >=', $desde)
            ->where('creada <', $hasta)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
        foreach ($items as $item) {
            $estado = match ((string) $item['estado']) {
                'en_pago' => 'en_proceso',
                'pagado'  => 'pagado',
                default   => 'pendiente',
            };
            $filas[] = [
                'id'      => (int) $item['id'],
                'nombre'  => $nombres[(int) $item['usuario_id']] ?? '',
                'motivo'  => 'Documento ' . $item['motivo'],
                'forma'   => $formas[(int) $item['forma_pago_id']] ?? '',
                'monto'   => (float) $item['monto'],
                'moneda'  => (string) $item['moneda'],
                'estado'  => $estado,
                'ruta'    => 'documentos',
            ];
        }

        return $filas;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function una(int $empresaId, int $id): ?array
    {
        $fila = db_connect()->table('documentos')->where('empresa_id', $empresaId)->where('id', $id)->get()->getRowArray();

        return is_array($fila) ? $fila : null;
    }
}
