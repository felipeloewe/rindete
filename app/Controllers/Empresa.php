<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\IdFiscal;
use App\Libraries\Identidad;
use App\Libraries\Paises;
use App\Libraries\Sesion;
use App\Models\EmpresaModel;

class Empresa extends BaseController
{
    public function index()
    {
        $sesion  = new Sesion();
        $usuario = $sesion->usuario();
        $empresa = $sesion->empresa();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/login');
        }
        if (($usuario['rol'] ?? '') !== 'administrador') {
            return redirect()->to('/panel');
        }

        return view('empresa/index', [
            'empresa' => $empresa,
            'usuario' => $sesion->usuario(),
            'paises'  => Paises::catalogo(),
        ]);
    }

    public function update()
    {
        $sesion  = new Sesion();
        $usuario = $sesion->usuario();
        $empresa = $sesion->empresa();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/login');
        }
        if ($usuario['rol'] !== 'administrador') {
            return redirect()->to('/empresa')->with('error', 'Solo el administrador puede editar la empresa.');
        }

        $nombre = trim((string) $this->request->getPost('nombre'));
        $pais   = strtoupper(trim((string) $this->request->getPost('pais')));
        $fiscal = IdFiscal::validar($pais, (string) $this->request->getPost('id_fiscal'));
        $errores = [];
        if ($nombre === '' || strlen($nombre) > 120) {
            $errores[] = 'Escribe el nombre de la empresa.';
        }
        if (Paises::pais($pais) === null) {
            $errores[] = 'Elige un país de la lista.';
        }
        if (! $fiscal['ok']) {
            $errores[] = $fiscal['error'];
        } else {
            $otra = model(EmpresaModel::class)->porFiscal($pais, $fiscal['valor']);
            if (is_array($otra) && (int) $otra['id'] !== (int) $empresa['id']) {
                $errores[] = 'Ese ' . Paises::pais($pais)['id_fiscal'] . ' ya tiene una cuenta.';
            }
        }
        $identidad = new Identidad();
        $whatsapp = null;
        if ($this->request->getPost('whatsapp') !== null) {
            $whatsapp = Identidad::telefono((string) $this->request->getPost('whatsapp'));
            if (! Identidad::telefonoValido($whatsapp)) {
                $errores[] = 'El WhatsApp de la empresa no es válido.';
            } elseif ($whatsapp !== '' && $identidad->whatsappOcupado($whatsapp, (int) $empresa['id'])) {
                $errores[] = 'Ese WhatsApp ya recibe para otra empresa.';
            }
        }
        $correo = null;
        if ($this->request->getPost('correo_boletas') !== null) {
            $correo = Identidad::correo((string) $this->request->getPost('correo_boletas'));
            if ($correo !== '' && ! IdFiscal::emailValido($correo)) {
                $errores[] = 'El correo de las boletas no es válido.';
            } elseif ($correo !== '' && $identidad->correoBoletasOcupado($correo, (int) $empresa['id'])) {
                $errores[] = 'Ese correo ya recibe boletas de otra empresa.';
            }
        }
        if ($errores !== []) {
            return redirect()->back()->withInput()->with('errores', $errores);
        }

        $datos = [
            'nombre'    => $nombre,
            'pais'      => $pais,
            'id_fiscal' => $fiscal['valor'],
        ];
        if ($whatsapp !== null) {
            $datos['whatsapp'] = $whatsapp;
        }
        if ($correo !== null) {
            $datos['correo_boletas'] = $correo;
        }
        model(EmpresaModel::class)->update($empresa['id'], $datos);

        return redirect()->to('/empresa')->with('ok', 'Empresa actualizada.');
    }
}
