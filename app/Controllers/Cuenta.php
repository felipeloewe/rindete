<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\BancosChile;
use App\Libraries\IdFiscal;
use App\Libraries\Paises;
use App\Libraries\PerfilEstado;
use App\Libraries\Sesion;
use App\Models\UsuarioModel;

class Cuenta extends BaseController
{
    public function index()
    {
        $sesion = new Sesion();
        $usuario = $sesion->usuario();
        $empresa = $sesion->empresa();
        $pais = is_array($empresa) ? Paises::pais((string) $empresa['pais']) : null;
        $codigo = is_array($usuario) ? (string) ($usuario['banco_destino'] ?? '') : '';

        return view('cuenta/index', [
            'titulo'   => 'Mi perfil',
            'usuario'  => $usuario,
            'empresa'  => $empresa,
            'pais'     => $pais,
            'banco'    => $codigo === '' ? '' : sprintf('%03d %s', (int) $codigo, BancosChile::CODIGOS[$codigo] ?? ''),
            'perfil'   => self::perfil(is_array($usuario) ? $usuario : []),
            'estado'   => is_array($usuario) ? (new PerfilEstado())->de($usuario) : [
                'trabajo' => [], 'fondos' => [], 'rendiciones' => [], 'alertas' => [],
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $usuario
     */
    private static function perfil(array $usuario): string
    {
        $admin = ($usuario['rol'] ?? '') === 'administrador';
        $rinde = (int) ($usuario['rinde'] ?? 0) === 1;
        if ($admin && $rinde) {
            return 'Administrador y usuario';
        }

        return $admin ? 'Administrador' : 'Usuario';
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
            $otro = model(UsuarioModel::class)->porEmail($email);
            if (is_array($otro) && (int) $otro['id'] !== (int) $actual['id']) {
                $errores[] = 'Ese correo ya tiene una cuenta.';
            }
        }
        $inicial = (int) ($actual['clave_inicial'] ?? 0) === 1;
        if ($inicial && strlen($password) < 8) {
            $errores[] = 'Cambia la contraseña inicial. Debe tener al menos 8 caracteres.';
        } elseif ($password !== '' && strlen($password) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($errores !== []) {
            return redirect()->back()->withInput()->with('errores', $errores);
        }

        $datos = ['nombre' => $nombre, 'email' => $email];
        if ($password !== '') {
            $datos['password'] = password_hash($password, PASSWORD_DEFAULT);
            $datos['clave_inicial'] = 0;
        }
        model(UsuarioModel::class)->update($actual['id'], $datos);

        return redirect()->to('/cuenta')->with('ok', 'Cuenta actualizada.');
    }
}
