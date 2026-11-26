<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\CorreoAcceso;
use App\Libraries\Incorporacion;
use App\Libraries\MaestroSesion;
use App\Libraries\Paises;
use App\Libraries\TableroMaestro;
use CodeIgniter\HTTP\RedirectResponse;

class Maestro extends BaseController
{
    public function entrada()
    {
        $maestro = (new MaestroSesion())->actual();
        if ($maestro === null) {
            return view('auth/master', ['usuario' => old('usuario', '')]);
        }
        if ((int) ($maestro['debe_cambiar'] ?? 0) === 1) {
            return redirect()->to('/master/clave');
        }

        return view('master/index', [
            'tablero' => (new TableroMaestro())->armar(),
            'paises'  => Paises::catalogo(),
        ]);
    }

    public function entrar(): RedirectResponse
    {
        $maestro = (new MaestroSesion())->entrar(
            (string) $this->request->getPost('usuario'),
            (string) $this->request->getPost('password'),
        );
        if ($maestro === null) {
            return redirect()->to('/master')->withInput()->with('error', 'Usuario o contraseña incorrectos.');
        }
        if ((int) ($maestro['debe_cambiar'] ?? 0) === 1) {
            return redirect()->to('/master/clave');
        }

        return redirect()->to('/master');
    }

    public function clave()
    {
        return view('master/clave');
    }

    public function guardarClave(): RedirectResponse
    {
        $resultado = (new MaestroSesion())->cambiar(
            (string) $this->request->getPost('actual'),
            (string) $this->request->getPost('password'),
            (string) $this->request->getPost('password2'),
        );
        if (! $resultado['ok']) {
            return redirect()->to('/master/clave')->with('errores', $resultado['errores']);
        }

        return redirect()->to('/master')->with('ok', 'Contraseña actualizada.');
    }

    public function salir(): RedirectResponse
    {
        (new MaestroSesion())->salir();

        return redirect()->to('/master');
    }

    public function crear(): RedirectResponse
    {
        $resultado = (new Incorporacion())->abrir($this->request->getPost() ?? []);
        if (! $resultado['ok']) {
            return redirect()->to('/master')->withInput()->with('errores', $resultado['errores']);
        }

        return $this->entregar('Empresa creada.', $resultado);
    }

    public function administrador(int $id): RedirectResponse
    {
        $resultado = (new Incorporacion())->administrador(
            $id,
            (string) $this->request->getPost('nombre'),
            (string) $this->request->getPost('email'),
        );
        if (! $resultado['ok']) {
            return redirect()->to('/master')->withInput()->with('errores', $resultado['errores']);
        }

        return $this->entregar('Administrador creado.', $resultado);
    }

    public function rendidor(int $id): RedirectResponse
    {
        $resultado = (new Incorporacion())->marcarRendidor($id, (string) $this->request->getPost('rinde') === '1');
        if (! $resultado['ok']) {
            return redirect()->to('/master')->with('errores', $resultado['errores']);
        }

        return redirect()->to('/master')->with('ok', (string) ($resultado['aviso'] ?? 'Listo.'));
    }

    public function vigencia(int $id): RedirectResponse
    {
        $vigente = (string) $this->request->getPost('vigente') === '1';
        if (! (new Incorporacion())->vigencia($id, $vigente)) {
            return redirect()->to('/master')->with('error', 'Esa empresa no está.');
        }

        return redirect()->to('/master')->with('ok', $vigente ? 'Empresa activa.' : 'Empresa en pausa. Sale del tablero de activas.');
    }

    /**
     * @param array{ok: bool, errores: list<string>, clave?: string, email?: string, nombre?: string, empresa?: string} $resultado
     */
    private function entregar(string $hecho, array $resultado): RedirectResponse
    {
        $email   = (string) ($resultado['email'] ?? '');
        $enviado = (new CorreoAcceso())->enviar(
            $email,
            (string) ($resultado['nombre'] ?? ''),
            (string) ($resultado['empresa'] ?? ''),
            (string) ($resultado['clave'] ?? ''),
        );
        if ($enviado) {
            return redirect()->to('/master')->with('ok', $hecho . ' La clave inicial se envió a ' . $email . '.');
        }

        return redirect()->to('/master')->with('ok', $hecho)->with('avisos', [
            'El correo no salió. Clave inicial de ' . $email . ': ' . (string) ($resultado['clave'] ?? ''),
        ]);
    }
}
