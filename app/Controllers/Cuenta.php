<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\IdFiscal;
use App\Libraries\Sesion;
use App\Models\UsuarioModel;

class Cuenta extends BaseController
{
    public function index()
    {
        return view('cuenta/index', ['usuario' => (new Sesion())->usuario()]);
    }

    public function update()
    {
        $sesion   = new Sesion();
        $actual   = $sesion->usuario();
        $nombre   = trim((string) $this->request->getPost('nombre'));
        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $errores  = [];

        if ($actual === null) {
            return redirect()->to('/login');
        }
        if ($nombre === '' || strlen($nombre) > 80) {
            $errores[] = 'Escribe tu nombre.';
        }
        if (! IdFiscal::emailValido($email)) {
            $errores[] = 'El correo no es válido.';
        } else {
            $otro = model(UsuarioModel::class)->where('email', $email)->where('id !=', $actual['id'])->first();
            if ($otro !== null) {
                $errores[] = 'Ese correo ya tiene una cuenta.';
            }
        }
        if ($password !== '' && strlen($password) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($errores !== []) {
            return redirect()->back()->withInput()->with('errores', $errores);
        }

        $datos = ['nombre' => $nombre, 'email' => $email];
        if ($password !== '') {
            $datos['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        model(UsuarioModel::class)->update($actual['id'], $datos);

        return redirect()->to('/cuenta')->with('ok', 'Cuenta actualizada.');
    }
}
