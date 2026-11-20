<?php

declare(strict_types=1);

namespace App\Filters;

use App\Libraries\PoliticaDatos;
use App\Libraries\Sesion;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $usuario = (new Sesion())->usuario();
        if ($usuario === null) {
            return redirect()->to('/login');
        }
        $ruta = trim(uri_string(), '/');
        if (! PoliticaDatos::aceptada($usuario) && $ruta !== 'politicas/aceptar') {
            return redirect()->to('/politicas/aceptar');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
