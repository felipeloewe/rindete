<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\AltaEmpresa;
use App\Libraries\Paises;
use App\Libraries\Sesion;

class Registro extends BaseController
{
    public function index()
    {
        if ((new Sesion())->usuario() !== null) {
            return redirect()->to('/panel');
        }

        return view('auth/registro', [
            'paises' => Paises::catalogo(),
            'old'    => [
                'pais'      => old('pais', 'CL'),
                'empresa'   => old('empresa', ''),
                'id_fiscal' => old('id_fiscal', ''),
                'nombre'    => old('nombre', ''),
                'email'     => old('email', ''),
            ],
        ]);
    }

    public function store()
    {
        $input = [
            'pais'      => (string) $this->request->getPost('pais'),
            'empresa'   => (string) $this->request->getPost('empresa'),
            'id_fiscal' => (string) $this->request->getPost('id_fiscal'),
            'nombre'    => (string) $this->request->getPost('nombre'),
            'email'     => (string) $this->request->getPost('email'),
            'password'  => (string) $this->request->getPost('password'),
        ];
        $alta = (new AltaEmpresa())->registrar($input);
        if (! $alta['ok']) {
            return redirect()->back()->withInput()->with('errores', $alta['errores']);
        }

        (new Sesion())->entrar($alta['usuario']);

        return redirect()->to('/panel')->with('ok', 'Empresa lista. Ya puedes entrar con el correo y la clave que elegiste.');
    }
}
