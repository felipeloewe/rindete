<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\IdFiscal;
use App\Libraries\Paises;
use App\Libraries\Sesion;
use App\Libraries\ZonaHoraria;
use App\Models\EmpresaModel;

class Empresa extends BaseController
{
    public function index()
    {
        $sesion  = new Sesion();
        $empresa = $sesion->empresa();

        return view('empresa/index', [
            'empresa'   => $empresa,
            'usuario'   => $sesion->usuario(),
            'paises'    => Paises::catalogo(),
            'timezones' => ZonaHoraria::opciones(),
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

        $politicas = $this->politicasDesdePost();
        if ($politicas['errores'] !== []) {
            $errores = array_merge($errores, $politicas['errores']);
        }

        if ($errores !== []) {
            return redirect()->back()->withInput()->with('errores', $errores);
        }

        model(EmpresaModel::class)->update($empresa['id'], array_merge([
            'nombre'    => $nombre,
            'pais'      => $pais,
            'id_fiscal' => $fiscal['valor'],
        ], $politicas['datos']));

        return redirect()->to('/empresa')->with('ok', 'Empresa actualizada.');
    }

    /**
     * @return array{datos: array<string, mixed>, errores: list<string>}
     */
    private function politicasDesdePost(): array
    {
        $errores = [];
        $detectar = (int) $this->request->getPost('detectar_duplicados') === 1 ? 1 : 0;
        $dias     = (int) $this->request->getPost('dias_duplicado');
        $desdeRaw = str_replace(',', '.', trim((string) $this->request->getPost('comprobante_desde')));
        $timezone = trim((string) $this->request->getPost('timezone'));

        if ($dias < 0 || $dias > 90) {
            $errores[] = 'Los días para detectar duplicados deben estar entre 0 y 90.';
        }
        if ($desdeRaw === '' || ! is_numeric($desdeRaw) || (float) $desdeRaw < 0) {
            $errores[] = 'El monto mínimo con comprobante no es válido.';
            $desde = 1.0;
        } else {
            $desde = round((float) $desdeRaw, 2);
        }
        if (! ZonaHoraria::valida($timezone) || ! in_array($timezone, ZonaHoraria::opciones(), true)) {
            $errores[] = 'Elige una zona horaria de la lista.';
            $timezone  = ZonaHoraria::DEFAULT;
        }

        return [
            'datos' => [
                'detectar_duplicados' => $detectar,
                'dias_duplicado'      => $dias,
                'comprobante_desde'   => $desde,
                'timezone'            => $timezone,
            ],
            'errores' => $errores,
        ];
    }
}
