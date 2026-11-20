<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\PoliticaDatos;
use App\Libraries\Sesion;
use App\Models\UsuarioModel;

class Politicas extends BaseController
{
    public function ver()
    {
        return view('politicas/ver', ['titulo' => 'Política de tratamiento']);
    }

    public function condiciones()
    {
        return view('politicas/condiciones', ['titulo' => 'Condiciones de uso']);
    }

    public function aceptar()
    {
        $usuario = (new Sesion())->usuario();
        if (PoliticaDatos::aceptada($usuario)) {
            return redirect()->to('/panel');
        }

        return view('politicas/aceptar');
    }

    public function registrar()
    {
        $usuario = (new Sesion())->usuario();
        if ($usuario === null) {
            return redirect()->to('/login');
        }
        if ((string) $this->request->getPost('acepta_politica') !== '1') {
            return redirect()->back()->with('error', 'Acepta las condiciones de uso y los efectos que se indican para usar tu cuenta.');
        }

        model(UsuarioModel::class)->update((int) $usuario['id'], PoliticaDatos::marca());

        return redirect()->to('/panel')->with('ok', 'Quedó registrada tu aceptación.');
    }
}
