<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\TarjetaModel;

class TarjetaIdentidad
{
    /** @var array<string, string> */
    public const EMISORES = [
        ''         => 'A mano',
        'global66' => 'Global66',
        'mach'     => 'MACH',
    ];

    /**
     * @param list<int> $personas
     * @param list<int> $gastos
     *
     * @return array{ok: bool, errores: list<string>}
     */
    public function guardar(
        int $empresaId,
        string $nombre,
        float $monto,
        bool $personal,
        array $personas,
        array $gastos,
        string $ultimos4,
        string $emisor,
        string $referencia,
        string $billetera,
        int $titularId,
    ): array {
        $ultimos4   = preg_replace('/\s+/', '', $ultimos4) ?? '';
        $emisor     = trim($emisor);
        $referencia = mb_substr(trim($referencia), 0, 80);
        $billetera  = trim($billetera);
        if (! isset(self::EMISORES[$emisor])) {
            return ['ok' => false, 'errores' => ['Elige si la identificas a mano, en Global66 o en MACH.']];
        }
        if ($ultimos4 !== '' && preg_match('/^\d{4}$/', $ultimos4) !== 1) {
            return ['ok' => false, 'errores' => ['Los últimos 4 dígitos identifican la tarjeta.']];
        }
        if ($ultimos4 === '' && $referencia === '') {
            return ['ok' => false, 'errores' => ['Identifica la tarjeta con sus últimos 4 dígitos o con el identificador de la cuenta.']];
        }
        if ($emisor !== 'global66') {
            $billetera = '';
        } elseif ($billetera !== '' && preg_match('/^\d+$/', $billetera) !== 1) {
            return ['ok' => false, 'errores' => ['El id de la billetera Global66 es numérico.']];
        }
        if ($this->repetida($empresaId, $emisor, 'ultimos4', $ultimos4)) {
            return ['ok' => false, 'errores' => ['Esa tarjeta ya está identificada.']];
        }
        if ($this->repetida($empresaId, $emisor, 'referencia', $referencia)) {
            return ['ok' => false, 'errores' => ['Esa cuenta ya está identificada.']];
        }
        $exclusiva = 0;
        $titular = null;
        if (! $personal) {
            if ($titularId <= 0) {
                return ['ok' => false, 'errores' => ['La tarjeta de la empresa necesita a la persona que rinde.']];
            }
            if (! in_array($titularId, $personas, true)) {
                return ['ok' => false, 'errores' => ['La persona que rinde tiene que tener acceso a la tarjeta.']];
            }
            $exclusiva = 1;
            $titular = $titularId;
        }
        $id = model(TarjetaModel::class)->insert([
            'empresa_id'     => $empresaId,
            'nombre'         => $nombre,
            'ultimos4'       => $ultimos4,
            'emisor'         => $emisor,
            'referencia'     => $referencia,
            'billetera'      => $billetera,
            'titular_id'     => $titular,
            'exclusiva'      => $exclusiva,
            'personal'       => $personal ? 1 : 0,
            'monto_aceptado' => $monto,
            'activa'         => 1,
        ], true);
        $db = db_connect();
        foreach ($personas as $persona) {
            $db->table('tarjeta_usuarios')->insert(['tarjeta_id' => $id, 'usuario_id' => $persona]);
        }
        foreach ($gastos as $categoria) {
            $db->table('tarjeta_categorias')->insert(['tarjeta_id' => $id, 'categoria_id' => $categoria]);
        }

        return ['ok' => true, 'errores' => []];
    }

    private function repetida(int $empresaId, string $emisor, string $campo, string $valor): bool
    {
        if ($valor === '') {
            return false;
        }

        return model(TarjetaModel::class)
            ->where('empresa_id', $empresaId)
            ->where('emisor', $emisor)
            ->where($campo, $valor)
            ->countAllResults() > 0;
    }
}
