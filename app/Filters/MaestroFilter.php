<?php

declare(strict_types=1);

namespace App\Filters;

use App\Libraries\MaestroSesion;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MaestroFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $maestro = (new MaestroSesion())->actual();
        if ($maestro === null) {
            return redirect()->to('/master');
        }
        $ruta = trim(uri_string(), '/');
        if ((int) ($maestro['debe_cambiar'] ?? 0) === 1 && ! in_array($ruta, ['master/clave', 'master/salir'], true)) {
            return redirect()->to('/master/clave');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
