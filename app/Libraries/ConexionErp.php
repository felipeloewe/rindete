<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\ConexionModel;

class ConexionErp
{
    /**
     * @return array{ok: bool, errores: list<string>, token: string}
     */
    public function crear(int $empresaId, string $nombre, string $sistema, string $destino, bool $vigente): array
    {
        $nombre = trim($nombre);
        if ($nombre === '' || strlen($nombre) > 120 || ! isset(SistemasErp::CATALOGO[$sistema])) {
            return ['ok' => false, 'errores' => ['Elige Softnet u Odoo y escribe cómo la reconoces.'], 'token' => ''];
        }
        if (! in_array($destino, ['asientos', 'pagos', 'ambos'], true)) {
            return ['ok' => false, 'errores' => ['Elige si sirve a los preasientos, a la nómina o a los dos.'], 'token' => ''];
        }
        $token = 'rnd_' . bin2hex(random_bytes(24));
        model(ConexionModel::class)->insert([
            'empresa_id'   => $empresaId,
            'nombre'       => $nombre,
            'sistema'      => $sistema,
            'destino'      => $destino,
            'vigente'      => $vigente ? 1 : 0,
            'token_huella' => hash('sha256', $token),
        ]);

        return ['ok' => true, 'errores' => [], 'token' => $token];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function porToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '' || strlen($token) > 80) {
            return null;
        }
        $fila = model(ConexionModel::class)->where('token_huella', hash('sha256', $token))->first();
        if (! is_array($fila) || (int) $fila['vigente'] !== 1 || ! isset(SistemasErp::CATALOGO[(string) $fila['sistema']])) {
            return null;
        }

        return $fila;
    }

    public function sirve(array $conexion, string $destino): bool
    {
        $actual = (string) ($conexion['destino'] ?? '');

        return $actual === 'ambos' || $actual === $destino;
    }
}
