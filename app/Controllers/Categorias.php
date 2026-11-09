<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\GastoServicio;
use App\Libraries\Montos;
use App\Libraries\Paises;
use App\Libraries\Sesion;
use App\Models\CategoriaModel;
use App\Models\GastoModel;

class Categorias extends BaseController
{
    public function index()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $empresa === null
                ? redirect()->to('/login')
                : redirect()->to('/panel')->with('error', 'Solo el administrador puede editar las categorías.');
        }
        (new GastoServicio())->asegurarCategorias($empresa);
        $editId = (int) ($this->request->getGet('id') ?? 0);
        $edit   = $editId > 0 ? $this->deLaEmpresa($editId, (int) $empresa['id']) : null;
        $conteo = [];
        foreach (model(GastoModel::class)->where('empresa_id', $empresa['id'])->findAll() as $gasto) {
            $clave = (int) $gasto['categoria_id'];
            $conteo[$clave] = ($conteo[$clave] ?? 0) + 1;
        }

        return view('categorias/index', [
            'items'  => model(CategoriaModel::class)->where('empresa_id', $empresa['id'])->orderBy('nombre', 'ASC')->findAll(),
            'edit'   => $edit,
            'conteo' => $conteo,
            'moneda' => Paises::pais((string) $empresa['pais'])['moneda'] ?? 'USD',
        ]);
    }

    public function crear()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/categorias');
        }
        $datos = $this->datos();
        if ($datos === null) {
            return redirect()->to('/categorias')->with('error', 'Escribe el nombre y un tope válido. Usa 0 si no hay límite.');
        }
        model(CategoriaModel::class)->insert([
            'empresa_id' => $empresa['id'],
            'nombre'     => $datos['nombre'],
            'detalle'    => $datos['detalle'],
            'tope'       => $datos['tope'],
            'activa'     => 1,
        ]);

        return redirect()->to('/categorias')->with('ok', 'Categoría creada.');
    }

    public function actualizar(int $id)
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/categorias');
        }
        $categoria = $this->deLaEmpresa($id, (int) $empresa['id']);
        $datos     = $this->datos();
        if ($categoria === null || $datos === null) {
            return redirect()->to('/categorias')->with('error', 'Escribe el nombre y un tope válido. Usa 0 si no hay límite.');
        }
        model(CategoriaModel::class)->update($id, [
            'nombre'  => $datos['nombre'],
            'detalle' => $datos['detalle'],
            'tope'    => $datos['tope'],
            'activa'  => $this->request->getPost('activa') ? 1 : 0,
        ]);
        (new GastoServicio())->reevaluar((int) $empresa['id']);

        return redirect()->to('/categorias')->with('ok', 'Categoría actualizada. Volvimos a leer los gastos con el nuevo tope.');
    }

    public function eliminar(int $id)
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/categorias');
        }
        $categoria = $this->deLaEmpresa($id, (int) $empresa['id']);
        if ($categoria === null) {
            return redirect()->to('/categorias')->with('error', 'No encontramos esa categoría.');
        }
        $usada = model(GastoModel::class)->where('categoria_id', $id)->countAllResults() > 0;
        if ($usada) {
            return redirect()->to('/categorias')->with('error', 'Esa categoría ya tiene gastos. Cámbiale el nombre o el tope, no la borres.');
        }
        model(CategoriaModel::class)->delete($id);

        return redirect()->to('/categorias')->with('ok', 'Categoría eliminada.');
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null}
     */
    private function admin(): array
    {
        $sesion   = new Sesion();
        $usuario  = $sesion->usuario();
        $empresa  = $sesion->empresa();
        if ($usuario === null || $empresa === null) {
            return [null, null];
        }
        if ($usuario['rol'] !== 'administrador') {
            return [null, $empresa];
        }

        return [$usuario, $empresa];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function deLaEmpresa(int $id, int $empresaId): ?array
    {
        $categoria = model(CategoriaModel::class)->find($id);
        if (! is_array($categoria) || (int) $categoria['empresa_id'] !== $empresaId) {
            return null;
        }

        return $categoria;
    }

    /**
     * @return array{nombre: string, detalle: string, tope: float}|null
     */
    private function datos(): ?array
    {
        $nombre  = trim((string) $this->request->getPost('nombre'));
        $detalle = mb_substr(trim((string) $this->request->getPost('detalle')), 0, 180);
        $tope    = Montos::parse((string) ($this->request->getPost('tope') ?? '0'));
        if ($nombre === '' || strlen($nombre) > 80 || $tope === null) {
            return null;
        }

        return ['nombre' => $nombre, 'detalle' => $detalle, 'tope' => $tope];
    }
}
