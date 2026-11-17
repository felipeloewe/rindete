<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\CategoriaModel;
use App\Models\TipoDocumentoModel;
use App\Models\UsuarioModel;

class ParametrosBase
{
    /**
     * @param array<string, mixed> $empresa
     */
    public static function sembrar(array $empresa): void
    {
        $empresaId = (int) $empresa['id'];
        $fila      = model(\App\Models\EmpresaModel::class)->find($empresaId);
        if (! is_array($fila) || (int) ($fila['parametros_sembrados'] ?? 0) === 1) {
            return;
        }
        $pais = Paises::pais((string) ($fila['pais'] ?? $empresa['pais'] ?? ''));
        if (model(TipoDocumentoModel::class)->where('empresa_id', $empresaId)->countAllResults() === 0) {
            foreach ($pais['docs'] ?? [] as $nombre) {
                model(TipoDocumentoModel::class)->insert([
                    'empresa_id'      => $empresaId,
                    'nombre'          => $nombre,
                    'monto_aceptado'  => 0,
                    'sin_comprobante' => 0,
                    'activo'          => 1,
                ]);
            }
        }
        $db = db_connect();
        $ya = $db->table('gastos_aceptados')
            ->join('categorias', 'categorias.id = gastos_aceptados.categoria_id')
            ->where('categorias.empresa_id', $empresaId)
            ->countAllResults();
        if ($ya === 0) {
            foreach (model(UsuarioModel::class)->where('empresa_id', $empresaId)->findAll() as $usuario) {
                foreach (model(CategoriaModel::class)->where('empresa_id', $empresaId)->findAll() as $categoria) {
                    $db->table('gastos_aceptados')->insert([
                        'usuario_id'   => $usuario['id'],
                        'categoria_id' => $categoria['id'],
                    ]);
                }
            }
        }
        model(\App\Models\EmpresaModel::class)->update($empresaId, ['parametros_sembrados' => 1]);
    }

    public static function sumarCategoria(int $empresaId, int $categoriaId): void
    {
        $empresa = model(\App\Models\EmpresaModel::class)->find($empresaId);
        if (is_array($empresa)) {
            self::sembrar($empresa);
        }
        $db = db_connect();
        foreach (model(UsuarioModel::class)->where('empresa_id', $empresaId)->findAll() as $usuario) {
            $existe = $db->table('gastos_aceptados')
                ->where('usuario_id', $usuario['id'])
                ->where('categoria_id', $categoriaId)
                ->countAllResults();
            if ($existe === 0) {
                $db->table('gastos_aceptados')->insert([
                    'usuario_id'   => $usuario['id'],
                    'categoria_id' => $categoriaId,
                ]);
            }
        }
    }

    public static function limpiarCategoria(int $categoriaId): void
    {
        $db = db_connect();
        $db->table('gastos_aceptados')->where('categoria_id', $categoriaId)->delete();
        $db->table('categoria_cuentas')->where('categoria_id', $categoriaId)->delete();
        $db->table('tarjeta_categorias')->where('categoria_id', $categoriaId)->delete();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function tipos(int $empresaId): array
    {
        return model(TipoDocumentoModel::class)->where('empresa_id', $empresaId)->orderBy('nombre', 'ASC')->findAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function tipo(int $empresaId, string $nombre): ?array
    {
        if ($nombre === '') {
            return null;
        }
        $fila = model(TipoDocumentoModel::class)
            ->where('empresa_id', $empresaId)
            ->where('nombre', $nombre)
            ->first();

        return is_array($fila) ? $fila : null;
    }

    public static function aceptado(int $empresaId, int $usuarioId, int $categoriaId): bool
    {
        $db    = db_connect();
        $total = $db->table('gastos_aceptados')
            ->join('categorias', 'categorias.id = gastos_aceptados.categoria_id')
            ->where('categorias.empresa_id', $empresaId)
            ->countAllResults();
        if ($total === 0) {
            return true;
        }

        return $db->table('gastos_aceptados')
            ->where('usuario_id', $usuarioId)
            ->where('categoria_id', $categoriaId)
            ->countAllResults() > 0;
    }

    public static function cuentaDeCategoria(int $categoriaId): int
    {
        $fila = db_connect()->table('categoria_cuentas')->where('categoria_id', $categoriaId)->get()->getRowArray();

        return is_array($fila) ? (int) $fila['cuenta_id'] : 0;
    }
}
