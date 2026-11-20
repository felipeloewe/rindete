<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\MediosConexion;
use App\Libraries\TrazadorMedios;
use CodeIgniter\HTTP\ResponseInterface;

class Webhooks extends BaseController
{
    public function global66(string $clave): ResponseInterface
    {
        return $this->recibir('global66', $clave, 'x-api-key');
    }

    public function mach(string $clave): ResponseInterface
    {
        return $this->recibir('mach', $clave, 'Authorization');
    }

    private function recibir(string $proveedor, string $clave, string $header): ResponseInterface
    {
        $conexion = new MediosConexion();
        $medio = $conexion->porClave($proveedor, $clave);
        if ($medio === null || ! $conexion->coincide($medio, $this->request->getHeaderLine($header))) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'La clave no corresponde.']);
        }
        $cuerpo = $this->request->getJSON(true);
        if (! is_array($cuerpo)) {
            $cuerpo = [];
        }
        $trazador = new TrazadorMedios();
        if ($proveedor === 'global66') {
            $trazador->global66((int) $medio['empresa_id'], $cuerpo);
        } else {
            $trazador->mach((int) $medio['empresa_id'], $cuerpo);
        }

        return $this->response->setJSON(['ok' => true]);
    }
}
