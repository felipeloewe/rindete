<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\Paises;
use App\Libraries\Recepcion;
use App\Libraries\Sesion;
use App\Libraries\TableroInicio;

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

        $tablero = $usuario === null ? ['abiertas' => [], 'proceso' => [], 'cerradas' => [], 'duplicados' => []] : (new TableroInicio())->armar($usuario);
        $entradas = $usuario === null ? [] : (new Recepcion())->pendientes($usuario);

        return view('panel', [
            'usuario'  => $usuario,
            'empresa'  => $empresa,
            'pais'     => $pais,
            'tablero'  => $tablero,
            'entradas' => $entradas,
            'moneda'   => (string) ($pais['moneda'] ?? 'CLP'),
        ]);
    }
}
