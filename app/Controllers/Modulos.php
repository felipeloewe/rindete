<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\Modulos as Catalogo;
use App\Libraries\Sesion;

class Modulos extends BaseController
{
    public function index()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $empresa === null
                ? redirect()->to('/login')
                : redirect()->to('/panel')->with('error', 'Solo el administrador contrata módulos.');
        }

        return view('modulos/index', [
            'gratis'      => Catalogo::GRATIS,
            'catalogo'    => Catalogo::CATALOGO,
            'contratados' => Catalogo::contratados((int) $empresa['id']),
        ]);
    }

    public function contratar()
    {
        return $this->cambiar(true);
    }

    public function dejar()
    {
        return $this->cambiar(false);
    }

    private function cambiar(bool $sumar)
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $empresa === null
                ? redirect()->to('/login')
                : redirect()->to('/panel')->with('error', 'Solo el administrador contrata módulos.');
        }
        $codigo = (string) $this->request->getPost('codigo');
        if (! isset(Catalogo::CATALOGO[$codigo])) {
            return redirect()->to('/modulos')->with('error', 'Ese módulo no está en el catálogo.');
        }
        if ($sumar) {
            Catalogo::contratar((int) $empresa['id'], $codigo);

            return redirect()->to('/modulos')->with('ok', Catalogo::CATALOGO[$codigo]['nombre'] . ' quedó contratado.');
        }
        Catalogo::dejar((int) $empresa['id'], $codigo);

        return redirect()->to('/modulos')->with('ok', Catalogo::CATALOGO[$codigo]['nombre'] . ' quedó fuera de la empresa.');
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null}
     */
    private function admin(): array
    {
        $sesion  = new Sesion();
        $usuario = $sesion->usuario();
        $empresa = $sesion->empresa();
        if ($usuario === null || $empresa === null || ($usuario['rol'] ?? '') !== 'administrador') {
            return [null, $empresa];
        }

        return [$usuario, $empresa];
    }
}
