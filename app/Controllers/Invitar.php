<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\Invitacion;
use App\Libraries\Sesion;

class Invitar extends BaseController
{
    public function show(string $token)
    {
        if ((new Sesion())->usuario() !== null) {
            return redirect()->to('/panel');
        }

        $invitacion = new Invitacion();
        $usuario    = $invitacion->porToken($token);
        if ($usuario === null) {
            return view('invitar/invalida');
        }

        return view('invitar/aceptar', [
            'token'   => $token,
            'usuario' => $usuario,
        ]);
    }

    public function aceptar(string $token)
    {
        if ((new Sesion())->usuario() !== null) {
            return redirect()->to('/panel');
        }

        $resultado = (new Invitacion())->aceptar($token, (string) $this->request->getPost('password'));
        if (! $resultado['ok']) {
            return redirect()->back()->withInput()->with('errores', $resultado['errores']);
        }

        (new Sesion())->entrar($resultado['usuario']);

        return redirect()->to('/panel')->with('ok', 'Cuenta activada. Bienvenido a Rindete.');
    }
}
