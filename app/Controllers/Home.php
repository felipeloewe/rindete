<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\Paises;
use App\Libraries\Sesion;

class Home extends BaseController
{
    public function index()
    {
        if ((new Sesion())->usuario() !== null) {
            return redirect()->to('/panel');
        }

        return view('home');
    }

    public function panel()
    {
        $sesion   = new Sesion();
        $usuario  = $sesion->usuario();
        $empresa  = $sesion->empresa();
        $pais     = $empresa === null ? null : Paises::pais((string) $empresa['pais']);

        return view('panel', [
            'usuario' => $usuario,
            'empresa' => $empresa,
            'pais'    => $pais,
        ]);
    }
}
