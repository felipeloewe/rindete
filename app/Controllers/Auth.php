<?php

declare(strict_types=1);

namespace App\Controllers;

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

    public function olvidar()
    {
        if ((new Sesion())->usuario() !== null) {
            return redirect()->to('/panel');
        }

        return view('auth/credenciales', ['email' => old('email', '')]);
    }

    public function reenviar()
    {
        if ((new Sesion())->usuario() !== null) {
            return redirect()->to('/panel');
        }
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $usuario = IdFiscal::emailValido($email) ? model(UsuarioModel::class)->porEmail($email) : null;
        if (is_array($usuario) && (int) ($usuario['activo'] ?? 0) === 1) {
            (new \App\Libraries\Credenciales())->renovar($usuario, true);
        }

        return redirect()->to('/login/credenciales')->with(
            'ok',
            'Si ese correo tiene una cuenta, le enviamos una clave nueva. Si no llega, pide al administrador de tu empresa que la reenvíe.',
        );
    }

    public function attempt()
    {
        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $usuario  = model(UsuarioModel::class)->porEmail($email);

        if (! IdFiscal::emailValido($email) || ! is_array($usuario) || (int) $usuario['activo'] !== 1 || ! password_verify($password, (string) $usuario['password'])) {
            return redirect()->back()->withInput()->with('error', 'Correo o contraseña incorrectos.');
        }

        (new Sesion())->entrar($usuario);
        if ((int) ($usuario['clave_inicial'] ?? 0) === 1) {
            return redirect()->to('/cuenta')->with('error', 'Cambia la contraseña inicial antes de seguir.');
        }

        return redirect()->to('/panel');
    }

    public function logout()
    {
        (new Sesion())->salir();

        return redirect()->to('/login');
    }
}
