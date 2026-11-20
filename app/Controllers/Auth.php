<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\AuthThrottle;
use App\Libraries\IdFiscal;
use App\Libraries\Sesion;
use App\Models\UsuarioModel;

class Auth extends BaseController
{
    public function login()
    {
        if ((new Sesion())->usuario() !== null) {
            return redirect()->to('/panel');
        }

        return view('auth/login', ['email' => old('email', '')]);
    }

    public function attempt()
    {
        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $ip       = $this->request->getIPAddress();
        $throttle = new AuthThrottle();

        if ($throttle->loginBloqueado($ip, $email)) {
            return redirect()->back()->withInput()->with('error', $throttle->mensajeBloqueo());
        }

        $usuario = model(UsuarioModel::class)->where('email', $email)->first();

        if (! IdFiscal::emailValido($email) || ! is_array($usuario) || (int) $usuario['activo'] !== 1 || ! password_verify($password, (string) $usuario['password'])) {
            $throttle->registrarFalloLogin($ip, $email);

            return redirect()->back()->withInput()->with('error', 'Correo o contraseña incorrectos.');
        }

        $throttle->limpiarLogin($ip, $email);
        (new Sesion())->entrar($usuario);

        return redirect()->to('/panel');
    }

    public function logout()
    {
        (new Sesion())->salir();

        return redirect()->to('/login');
    }
}
