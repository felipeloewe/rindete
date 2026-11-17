<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\Paises;
use App\Libraries\RendicionServicio;
use App\Libraries\Sesion;
use App\Models\GastoModel;
use App\Models\RendicionModel;

class Rendiciones extends BaseController
{
    public function index()
    {
        [$usuario, $empresa] = $this->cuenta();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/login');
        }

        $consulta = model(RendicionModel::class)->where('empresa_id', $empresa['id']);
        if ($usuario['rol'] === 'rendidor') {
            $consulta->where('usuario_id', $usuario['id']);
        }
        $items = $consulta->orderBy('id', 'DESC')->findAll();
        $totales = [];
        foreach ($items as $item) {
            $gastos = model(GastoModel::class)->where('rendicion_id', $item['id'])->findAll();
            $totales[(int) $item['id']] = [
                'cantidad' => count($gastos),
                'monto'    => array_sum(array_map(static fn (array $gasto): float => (float) $gasto['monto'], $gastos)),
                'moneda'   => $gastos[0]['moneda'] ?? (Paises::pais((string) $empresa['pais'])['moneda'] ?? 'CLP'),
            ];
        }

        return view('rendiciones/index', [
            'items'   => $items,
            'totales' => $totales,
        ]);
    }

    public function nueva()
    {
        [$usuario, $empresa] = $this->cuenta();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/login');
        }

        return view('rendiciones/nueva');
    }

    public function crear()
    {
        [$usuario, $empresa] = $this->cuenta();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/login');
        }

        $resultado = (new RendicionServicio())->crear(
            $usuario,
            (string) $this->request->getPost('titulo'),
            (string) $this->request->getPost('nota'),
        );
        if (! $resultado['ok']) {
            return redirect()->to('/rendiciones/nueva')->with('errores', $resultado['errores'])->withInput();
        }

        return redirect()->to('/rendiciones/' . $resultado['id'])->with('ok', 'Rendición abierta. Suma los gastos legalizados.');
    }

    public function ver(int $id)
    {
        [$usuario, $empresa, $rendicion] = $this->visible($id);
        if ($rendicion === null || $empresa === null) {
            return redirect()->to('/rendiciones')->with('error', 'No encontramos esa rendición.');
        }

        $gastos = model(GastoModel::class)->where('rendicion_id', $rendicion['id'])->orderBy('fecha', 'DESC')->findAll();
        $disponibles = [];
        if ((string) $rendicion['estado'] === 'abierta') {
            $disponibles = model(GastoModel::class)
                ->where('empresa_id', $rendicion['empresa_id'])
                ->where('usuario_id', $rendicion['usuario_id'])
                ->where('estado', 'legalizado')
                ->where('rendicion_id', null)
                ->orderBy('fecha', 'DESC')
                ->findAll();
        }
        $moneda = $gastos[0]['moneda'] ?? (Paises::pais((string) $empresa['pais'])['moneda'] ?? 'CLP');

        return view('rendiciones/ver', [
            'rendicion'   => $rendicion,
            'gastos'      => $gastos,
            'disponibles' => $disponibles,
            'total'       => array_sum(array_map(static fn (array $gasto): float => (float) $gasto['monto'], $gastos)),
            'moneda'      => $moneda,
            'abierta'     => (string) $rendicion['estado'] === 'abierta',
        ]);
    }

    public function agregar(int $id)
    {
        [$usuario, $empresa, $rendicion] = $this->visible($id);
        if ($rendicion === null) {
            return redirect()->to('/rendiciones')->with('error', 'No encontramos esa rendición.');
        }
        $gasto = model(GastoModel::class)->find((int) $this->request->getPost('gasto_id'));
        if (! is_array($gasto)) {
            return redirect()->to('/rendiciones/' . $id)->with('error', 'Elige un gasto legalizado.');
        }
        $resultado = (new RendicionServicio())->agregar($rendicion, $gasto);
        if (! $resultado['ok']) {
            return redirect()->to('/rendiciones/' . $id)->with('errores', $resultado['errores']);
        }

        return redirect()->to('/rendiciones/' . $id)->with('ok', 'Gasto sumado. Quedó en rendición.');
    }

    public function quitar(int $id, int $gastoId)
    {
        [$usuario, $empresa, $rendicion] = $this->visible($id);
        if ($rendicion === null) {
            return redirect()->to('/rendiciones')->with('error', 'No encontramos esa rendición.');
        }
        $gasto = model(GastoModel::class)->find($gastoId);
        if (! is_array($gasto)) {
            return redirect()->to('/rendiciones/' . $id)->with('error', 'No encontramos ese gasto.');
        }
        $resultado = (new RendicionServicio())->quitar($rendicion, $gasto);
        if (! $resultado['ok']) {
            return redirect()->to('/rendiciones/' . $id)->with('errores', $resultado['errores']);
        }

        return redirect()->to('/rendiciones/' . $id)->with('ok', 'Gasto devuelto a legalizado.');
    }

    public function enviar(int $id)
    {
        [$usuario, $empresa, $rendicion] = $this->visible($id);
        if ($rendicion === null) {
            return redirect()->to('/rendiciones')->with('error', 'No encontramos esa rendición.');
        }
        $resultado = (new RendicionServicio())->enviar($rendicion);
        if (! $resultado['ok']) {
            return redirect()->to('/rendiciones/' . $id)->with('errores', $resultado['errores']);
        }

        return redirect()->to('/rendiciones/' . $id)->with('ok', 'Rendición enviada.');
    }

    public function eliminar(int $id)
    {
        [$usuario, $empresa, $rendicion] = $this->visible($id);
        if ($rendicion === null) {
            return redirect()->to('/rendiciones')->with('error', 'No encontramos esa rendición.');
        }
        $resultado = (new RendicionServicio())->eliminar($rendicion);
        if (! $resultado['ok']) {
            return redirect()->to('/rendiciones/' . $id)->with('errores', $resultado['errores']);
        }

        return redirect()->to('/rendiciones')->with('ok', 'Rendición eliminada. Los gastos volvieron a quedar libres.');
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null}
     */
    private function cuenta(): array
    {
        $sesion  = new Sesion();
        $usuario = $sesion->usuario();
        $empresa = $sesion->empresa();

        return [$usuario, $empresa];
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null, 2: array<string, mixed>|null}
     */
    private function visible(int $id): array
    {
        [$usuario, $empresa] = $this->cuenta();
        if ($usuario === null || $empresa === null) {
            return [null, null, null];
        }
        $rendicion = model(RendicionModel::class)->find($id);
        if (! is_array($rendicion) || (int) $rendicion['empresa_id'] !== (int) $empresa['id']) {
            return [$usuario, $empresa, null];
        }
        if ($usuario['rol'] === 'rendidor' && (int) $rendicion['usuario_id'] !== (int) $usuario['id']) {
            return [$usuario, $empresa, null];
        }

        return [$usuario, $empresa, $rendicion];
    }
}
