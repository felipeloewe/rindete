<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\MediosConexion;
use App\Libraries\Recepcion;
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

    public function whatsappVerificar(): ResponseInterface
    {
        $modo = (string) $this->request->getGet('hub_mode');
        $token = (string) $this->request->getGet('hub_verify_token');
        $reto = (string) $this->request->getGet('hub_challenge');
        if ($modo === 'subscribe' && $this->coincide($this->secreto('whatsapp.verifyToken'), $token)) {
            return $this->response->setStatusCode(200)->setBody($reto);
        }

        return $this->response->setStatusCode(403)->setBody('El token no corresponde.');
    }

    public function whatsapp(): ResponseInterface
    {
        $cuerpo = $this->request->getJSON(true);
        if (! is_array($cuerpo)) {
            $cuerpo = [];
        }
        try {
            (new Recepcion())->whatsapp($cuerpo);
        } catch (\Throwable $e) {
            log_message('error', 'WhatsApp: {mensaje}', ['mensaje' => $e->getMessage()]);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    public function correo(): ResponseInterface
    {
        if (! $this->coincide($this->secreto('correo.secreto'), $this->request->getHeaderLine('X-Rindete-Correo'))) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Falta la clave de recepción.']);
        }
        $cuerpo = $this->request->getJSON(true);
        if (! is_array($cuerpo)) {
            $cuerpo = [];
        }
        $bytes = base64_decode((string) ($cuerpo['archivo'] ?? ''), true);
        $entro = (new Recepcion())->correo(
            (string) ($cuerpo['hacia'] ?? ''),
            (string) ($cuerpo['desde'] ?? ''),
            (string) ($cuerpo['texto'] ?? ''),
            (string) ($cuerpo['mensaje_id'] ?? ''),
            is_string($bytes) ? $bytes : '',
            (string) ($cuerpo['extension'] ?? ''),
        );

        return $this->response->setJSON(['ok' => true, 'entro' => $entro]);
    }

    private function secreto(string $clave): string
    {
        $valor = getenv($clave);
        if (is_string($valor) && $valor !== '') {
            return $valor;
        }
        $deEntorno = env($clave);

        return is_string($deEntorno) ? $deEntorno : '';
    }

    private function coincide(string $esperado, string $dado): bool
    {
        return $esperado !== '' && strlen($esperado) === strlen($dado) && hash_equals($esperado, $dado);
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
