<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\IdFiscal;
use App\Libraries\Paises;
use App\Libraries\Sesion;
use App\Models\EmpresaModel;

class Empresa extends BaseController
{
    public function index()
    {
        $sesion  = new Sesion();
        $empresa = $sesion->empresa();

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
            $otra = model(EmpresaModel::class)
                ->where('pais', $pais)
                ->where('id_fiscal', $fiscal['valor'])
                ->where('id !=', $empresa['id'])
                ->first();
            if ($otra !== null) {
                $errores[] = 'Ese ' . Paises::pais($pais)['id_fiscal'] . ' ya tiene una cuenta.';
            }
        }
        if ($errores !== []) {
            return redirect()->back()->withInput()->with('errores', $errores);
        }

        model(EmpresaModel::class)->update($empresa['id'], [
            'nombre'    => $nombre,
            'pais'      => $pais,
            'id_fiscal' => $fiscal['valor'],
        ]);

        return redirect()->to('/empresa')->with('ok', 'Empresa actualizada.');
    }
}
