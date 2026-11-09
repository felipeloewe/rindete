<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\Comprobante;
use App\Libraries\GastoServicio;
use App\Libraries\Paises;
use App\Libraries\Sesion;
use App\Models\CategoriaModel;
use App\Models\GastoModel;
use App\Models\UsuarioModel;

class Gastos extends BaseController
{
    public function index()
    {
        $sesion  = new Sesion();
        $usuario = $sesion->usuario();
        $empresa = $sesion->empresa();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/login');
        }
        (new GastoServicio())->asegurarCategorias($empresa);

        $q          = trim((string) $this->request->getGet('q'));
        $estado     = (string) $this->request->getGet('estado');
        $categoria  = (string) $this->request->getGet('categoria');
        $consulta   = model(GastoModel::class)->where('empresa_id', $empresa['id']);
        if ($usuario['rol'] === 'rendidor') {
            $consulta->where('usuario_id', $usuario['id']);
        }
        if ($estado !== '') {
            $consulta->where('estado', $estado);
        }
        if ($categoria !== '') {
            $consulta->where('categoria_id', $categoria);
        }
        if ($q !== '') {
            $consulta->groupStart()
                ->like('concepto', $q)
                ->orLike('proveedor', $q)
                ->orLike('id_fiscal', $q)
                ->orLike('num_doc', $q)
                ->orLike('codigo_fiscal', $q)
                ->groupEnd();
        }
        $items = $consulta->orderBy('fecha', 'DESC')->orderBy('id', 'DESC')->findAll();

        return view('gastos/index', [
            'items'      => $items,
            'q'          => $q,
            'estado'     => $estado,
            'categoria'  => $categoria,
            'categorias' => $this->categorias((int) $empresa['id']),
            'nombres'    => $this->nombres((int) $empresa['id']),
            'personas'   => $this->personas((int) $empresa['id']),
        ]);
    }

    public function nuevo()
    {
        [$usuario, $empresa] = $this->cuenta();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/login');
        }
        (new GastoServicio())->asegurarCategorias($empresa);

        return view('gastos/form', $this->formulario($empresa, null));
    }

    public function crear()
    {
        [$usuario, $empresa] = $this->cuenta();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/login');
        }
        (new GastoServicio())->asegurarCategorias($empresa);
        $resultado = (new GastoServicio())->crear($usuario, $empresa, $this->request->getPost() ?? [], $this->request->getFile('comprobante'));
        if (! $resultado['ok']) {
            return redirect()->back()->withInput()->with('errores', $resultado['errores']);
        }

        return redirect()->to('/gastos/' . $resultado['gasto']['id'])->with('ok', $this->aviso($resultado['gasto']));
    }

    public function ver(int $id)
    {
        [$usuario, $empresa, $gasto] = $this->gastoVisible($id);
        if ($gasto === null) {
            return redirect()->to('/gastos')->with('error', 'No encontramos ese gasto.');
        }
        $pais      = Paises::pais((string) $empresa['pais']);
        $categoria = model(CategoriaModel::class)->find($gasto['categoria_id']);
        $persona   = model(UsuarioModel::class)->find($gasto['usuario_id']);

        return view('gastos/ver', [
            'gasto'      => $gasto,
            'pais'       => $pais,
            'categoria'  => $categoria,
            'persona'    => $persona,
            'puedeEditar'=> ! (new GastoServicio())->bloqueado($gasto) && ($usuario['rol'] !== 'rendidor' || (int) $gasto['usuario_id'] === (int) $usuario['id']),
        ]);
    }

    public function editar(int $id)
    {
        [$usuario, $empresa, $gasto] = $this->gastoVisible($id);
        if ($gasto === null) {
            return redirect()->to('/gastos')->with('error', 'No encontramos ese gasto.');
        }
        if ((new GastoServicio())->bloqueado($gasto)) {
            return redirect()->to('/gastos/' . $id)->with('error', 'Este gasto está en una rendición y no se puede editar.');
        }

        return view('gastos/form', $this->formulario($empresa, $gasto));
    }

    public function actualizar(int $id)
    {
        [$usuario, $empresa, $gasto] = $this->gastoVisible($id);
        if ($gasto === null) {
            return redirect()->to('/gastos')->with('error', 'No encontramos ese gasto.');
        }
        $resultado = (new GastoServicio())->actualizar($usuario, $empresa, $gasto, $this->request->getPost() ?? [], $this->request->getFile('comprobante'));
        if (! $resultado['ok']) {
            return redirect()->back()->withInput()->with('errores', $resultado['errores']);
        }

        return redirect()->to('/gastos/' . $id)->with('ok', $this->aviso($resultado['gasto']));
    }

    public function eliminar(int $id)
    {
        [$usuario, $empresa, $gasto] = $this->gastoVisible($id);
        unset($usuario, $empresa);
        if ($gasto === null) {
            return redirect()->to('/gastos')->with('error', 'No encontramos ese gasto.');
        }
        $resultado = (new GastoServicio())->eliminar($gasto);
        if (! $resultado['ok']) {
            return redirect()->to('/gastos/' . $id)->with('errores', $resultado['errores']);
        }

        return redirect()->to('/gastos')->with('ok', 'Gasto eliminado.');
    }

    public function comprobante(int $id)
    {
        [, , $gasto] = $this->gastoVisible($id);
        if ($gasto === null) {
            return $this->response->setStatusCode(404);
        }
        $ruta = (new Comprobante())->ruta((string) $gasto['comprobante']);
        if ($ruta === null) {
            return $this->response->setStatusCode(404);
        }

        return $this->response
            ->setHeader('Content-Type', (new Comprobante())->mime((string) $gasto['comprobante']))
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($ruta) . '"')
            ->setBody(file_get_contents($ruta) ?: '');
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null}
     */
    private function cuenta(): array
    {
        $sesion = new Sesion();

        return [$sesion->usuario(), $sesion->empresa()];
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null, 2: array<string, mixed>|null}
     */
    private function gastoVisible(int $id): array
    {
        [$usuario, $empresa] = $this->cuenta();
        if ($usuario === null || $empresa === null) {
            return [null, null, null];
        }
        $gasto = model(GastoModel::class)->find($id);
        if (! is_array($gasto) || (int) $gasto['empresa_id'] !== (int) $empresa['id']) {
            return [$usuario, $empresa, null];
        }
        if ($usuario['rol'] === 'rendidor' && (int) $gasto['usuario_id'] !== (int) $usuario['id']) {
            return [$usuario, $empresa, null];
        }

        return [$usuario, $empresa, $gasto];
    }

    /**
     * @param array<string, mixed>      $empresa
     * @param array<string, mixed>|null $gasto
     *
     * @return array<string, mixed>
     */
    private function formulario(array $empresa, ?array $gasto): array
    {
        $pais = Paises::pais((string) $empresa['pais']);
        $valor = static function (string $campo, string $defecto = '') use ($gasto): string {
            $previo = old($campo);
            if ($previo !== null) {
                return (string) $previo;
            }
            if ($gasto !== null && array_key_exists($campo, $gasto)) {
                return (string) $gasto[$campo];
            }

            return $defecto;
        };

        return [
            'gasto'      => $gasto,
            'pais'       => $pais,
            'moneda'     => (string) ($gasto['moneda'] ?? ($pais['moneda'] ?? 'USD')),
            'valor'      => $valor,
            'categorias' => model(CategoriaModel::class)->where('empresa_id', $empresa['id'])->orderBy('nombre', 'ASC')->findAll(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function categorias(int $empresaId): array
    {
        return model(CategoriaModel::class)->where('empresa_id', $empresaId)->orderBy('nombre', 'ASC')->findAll();
    }

    /**
     * @return array<int, string>
     */
    private function nombres(int $empresaId): array
    {
        $nombres = [];
        foreach ($this->categorias($empresaId) as $categoria) {
            $nombres[(int) $categoria['id']] = (string) $categoria['nombre'];
        }

        return $nombres;
    }

    /**
     * @return array<int, string>
     */
    private function personas(int $empresaId): array
    {
        $personas = [];
        foreach (model(UsuarioModel::class)->where('empresa_id', $empresaId)->findAll() as $persona) {
            $personas[(int) $persona['id']] = (string) $persona['nombre'];
        }

        return $personas;
    }

    /**
     * @param array<string, mixed> $gasto
     */
    private function aviso(array $gasto): string
    {
        return (string) $gasto['estado'] === 'observado'
            ? 'Gasto guardado con observaciones. Revísalas antes de rendir.'
            : 'Gasto legalizado. Ya puedes incluirlo en una rendición.';
    }
}
