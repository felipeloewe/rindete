<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\Invitacion;
use App\Libraries\Sesion;
use App\Models\UsuarioModel;

class Usuarios extends BaseController
{
    public function index()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/panel')->with('error', 'Solo el administrador gestiona personas.');
        }

        $personas = model(UsuarioModel::class)
            ->where('empresa_id', $empresa['id'])
            ->orderBy('rol', 'ASC')
            ->orderBy('nombre', 'ASC')
            ->findAll();

        return view('usuarios/index', [
            'usuario'  => $usuario,
            'empresa'  => $empresa,
            'personas' => $personas,
            'enlace'   => session('invite_link'),
        ]);
    }

    public function invitar()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/panel')->with('error', 'Solo el administrador gestiona personas.');
        }

        $resultado = (new Invitacion())->invitar(
            $usuario,
            $empresa,
            (string) $this->request->getPost('nombre'),
            (string) $this->request->getPost('email'),
        );
        if (! $resultado['ok']) {
            return redirect()->back()->withInput()->with('errores', $resultado['errores']);
        }

        $mensaje = $resultado['enviado']
            ? 'Invitación enviada por correo.'
            : 'Invitación creada. Copia el enlace y compártelo (SMTP no configurado).';

        return redirect()->to('/usuarios')
            ->with('ok', $mensaje)
            ->with('invite_link', $resultado['enlace'] ?? '');
    }

    public function desactivar(int $id)
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/panel')->with('error', 'Solo el administrador gestiona personas.');
        }

        $resultado = (new Invitacion())->desactivar($usuario, $empresa, $id);
        if (! $resultado['ok']) {
            return redirect()->to('/usuarios')->with('errores', $resultado['errores']);
        }

        return redirect()->to('/usuarios')->with('ok', 'Persona desactivada.');
    }

    public function activar(int $id)
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/panel')->with('error', 'Solo el administrador gestiona personas.');
        }

        $resultado = (new Invitacion())->activar($usuario, $empresa, $id);
        if (! $resultado['ok']) {
            return redirect()->to('/usuarios')->with('errores', $resultado['errores']);
        }

        return redirect()->to('/usuarios')->with('ok', 'Persona activada.');
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null}
     */
    private function admin(): array
    {
        $sesion  = new Sesion();
        $usuario = $sesion->usuario();
        $empresa = $sesion->empresa();
        if ($usuario === null || $empresa === null || $usuario['rol'] !== 'administrador') {
            return [null, null];
        }

        return [$usuario, $empresa];
    }
}
