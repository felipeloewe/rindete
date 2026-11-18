<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\BancosChile;
use App\Libraries\Paises;
use App\Libraries\PersonaServicio;
use App\Libraries\Planes;
use App\Libraries\Sesion;
use App\Models\UsuarioModel;

class Personas extends BaseController
{
    public function index()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null) {
            return $this->fuera($empresa);
        }

        $personas = model(UsuarioModel::class)->where('empresa_id', $empresa['id'])->orderBy('id', 'ASC')->findAll();
        $cupo     = (int) ($empresa['cupo_usuarios'] ?? Planes::USUARIOS);
        $pais     = Paises::pais((string) $empresa['pais']);

        return view('personas/index', [
            'personas'   => $personas,
            'propias'    => (int) $usuario['id'],
            'usadas'     => count($personas),
            'cupo'       => $cupo,
            'puedeSumar' => Planes::puedeSumarUsuario(count($personas), $cupo),
            'documento'  => $pais['id_fiscal'] ?? 'RUT',
            'ejemplo'    => $pais['ejemplo'] ?? '',
            'chile'      => (string) $empresa['pais'] === 'CL',
            'bancos'     => BancosChile::CODIGOS,
        ]);
    }

    public function crear()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null) {
            return $this->fuera($empresa);
        }

        $resultado = (new PersonaServicio())->crear($empresa, $this->request->getPost() ?? []);
        if (! $resultado['ok']) {
            return redirect()->to('/personas')->with('errores', $resultado['errores'])->withInput();
        }

        return redirect()->to('/personas')->with('ok', 'Persona agregada. Ya puede entrar con su correo.');
    }

    public function guardar(int $id)
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null) {
            return $this->fuera($empresa);
        }

        $persona = model(UsuarioModel::class)->find($id);
        if (! is_array($persona) || (int) $persona['empresa_id'] !== (int) $empresa['id']) {
            return redirect()->to('/personas')->with('error', 'No encontramos a esa persona.');
        }
        $resultado = (new PersonaServicio())->guardar($usuario, $persona, $this->request->getPost() ?? []);
        if (! $resultado['ok']) {
            return redirect()->to('/personas')->with('errores', $resultado['errores']);
        }

        return redirect()->to('/personas')->with('ok', 'Datos de la persona guardados.');
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
            return [null, $empresa];
        }

        return [$usuario, $empresa];
    }

    /**
     * @param array<string, mixed>|null $empresa
     */
    private function fuera(?array $empresa)
    {
        return $empresa === null
            ? redirect()->to('/login')
            : redirect()->to('/panel')->with('error', 'Solo el administrador define a las personas.');
    }
}
