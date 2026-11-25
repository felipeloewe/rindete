<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\MedioModel;

class MediosConexion
{
    /** @var array<string, string> */
    public const PROVEEDORES = [
        'global66' => 'Global66',
        'mach'     => 'MACH',
    ];

    /**
     * @return array{ok: bool, errores: list<string>}
     */
    public function guardar(int $empresaId, string $proveedor, string $secreto, string $tokenConsulta, ?string $clientId = null, ?string $clientSecret = null, ?string $credencial = null): array
    {
        $proveedor = trim($proveedor);
        $secreto = trim($secreto);
        $tokenConsulta = trim($tokenConsulta);
        if (! isset(self::PROVEEDORES[$proveedor])) {
            return ['ok' => false, 'errores' => ['Elige Global66 o MACH.']];
        }
        if ($secreto === '' || strlen($secreto) > 500) {
            return ['ok' => false, 'errores' => ['Pega la clave del webhook.']];
        }
        if ($proveedor !== 'global66') {
            $tokenConsulta = '';
            $clientId = null;
            $clientSecret = null;
            $credencial = null;
        }
        if (strlen($tokenConsulta) > 500) {
            return ['ok' => false, 'errores' => ['El token de consulta de Global66 no cabe.']];
        }
        $modelo = model(MedioModel::class);
        $actual = $modelo->where('empresa_id', $empresaId)->where('proveedor', $proveedor)->first();
        $api = $this->credencial($clientId, $clientSecret, $credencial, is_array($actual) ? $actual : null);
        if (! $api['ok']) {
            return ['ok' => false, 'errores' => $api['errores']];
        }
        $cifrado = base64_encode(service('encrypter')->encrypt($secreto));
        $consulta = $tokenConsulta === '' ? null : base64_encode(service('encrypter')->encrypt($tokenConsulta));
        $datos = [
            'secreto'        => $cifrado,
            'token_consulta' => $consulta,
            'vigente'        => 1,
        ];
        if ($api['aplica']) {
            $datos['credencial'] = $api['credencial'];
            $datos['client_id'] = $api['client_id'];
            $datos['client_secret'] = $api['client_secret'];
        }
        if (is_array($actual)) {
            $modelo->update($actual['id'], $datos);
        } else {
            $modelo->insert($datos + [
                'empresa_id' => $empresaId,
                'proveedor'  => $proveedor,
                'clave'      => bin2hex(random_bytes(16)),
            ]);
        }

        return ['ok' => true, 'errores' => []];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function porClave(string $proveedor, string $clave): ?array
    {
        $clave = trim($clave);
        if (! isset(self::PROVEEDORES[$proveedor]) || $clave === '' || strlen($clave) > 64) {
            return null;
        }
        $fila = model(MedioModel::class)->where('proveedor', $proveedor)->where('clave', $clave)->where('vigente', 1)->first();

        return is_array($fila) ? $fila : null;
    }

    /**
     * @param array<string, mixed> $medio
     */
    public function coincide(array $medio, string $presentado): bool
    {
        $secreto = $this->leer((string) ($medio['secreto'] ?? ''));
        $presentado = trim($presentado);
        if ($this->igual($secreto, $presentado)) {
            return true;
        }
        if (str_starts_with(strtolower($presentado), 'bearer ')) {
            return $this->igual($secreto, trim(substr($presentado, 7)));
        }

        return false;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function de(int $empresaId, string $proveedor): ?array
    {
        $fila = model(MedioModel::class)->where('empresa_id', $empresaId)->where('proveedor', $proveedor)->where('vigente', 1)->first();

        return is_array($fila) ? $fila : null;
    }

    /**
     * @param array<string, mixed> $medio
     */
    public function tokenConsulta(array $medio): string
    {
        return $this->leer((string) ($medio['token_consulta'] ?? ''));
    }

    /**
     * @param array<string, mixed> $medio
     */
    public function clientSecret(array $medio): string
    {
        return $this->leer((string) ($medio['client_secret'] ?? ''));
    }

    public function guardarConsulta(int $id, string $token): void
    {
        $token = trim($token);
        if ($token === '' || strlen($token) > 8000) {
            return;
        }
        model(MedioModel::class)->update($id, [
            'token_consulta' => base64_encode(service('encrypter')->encrypt($token)),
        ]);
    }

    public function url(array $medio): string
    {
        return site_url('webhooks/' . $medio['proveedor'] . '/' . $medio['clave']);
    }

    /**
     * @param array<string, mixed>|null $actual
     *
     * @return array{ok: bool, errores: list<string>, aplica: bool, credencial: string, client_id: string, client_secret: ?string}
     */
    private function credencial(?string $clientId, ?string $clientSecret, ?string $nombre, ?array $actual): array
    {
        $vacio = ['ok' => true, 'errores' => [], 'aplica' => false, 'credencial' => '', 'client_id' => '', 'client_secret' => null];
        if ($clientId === null && $clientSecret === null && $nombre === null) {
            return $vacio;
        }
        $clientId = trim((string) $clientId);
        $clientSecret = trim((string) $clientSecret);
        $nombre = mb_substr(trim((string) $nombre), 0, 120);
        if ($clientId === '' && $clientSecret === '' && $nombre === '') {
            return $vacio;
        }
        if (strlen($clientId) > 120) {
            return ['ok' => false, 'errores' => ['El Client ID de Global66 no cabe.'], 'aplica' => false, 'credencial' => '', 'client_id' => '', 'client_secret' => null];
        }
        if ($clientId === '') {
            return ['ok' => false, 'errores' => ['Falta el Client ID de Global66.'], 'aplica' => false, 'credencial' => '', 'client_id' => '', 'client_secret' => null];
        }
        if (strlen($clientSecret) > 500) {
            return ['ok' => false, 'errores' => ['El Client Secret de Global66 no cabe.'], 'aplica' => false, 'credencial' => '', 'client_id' => '', 'client_secret' => null];
        }
        $guardado = is_array($actual) ? (string) ($actual['client_secret'] ?? '') : '';
        if ($clientSecret === '') {
            if ($guardado === '') {
                return ['ok' => false, 'errores' => ['Pega el Client Secret de Global66.'], 'aplica' => false, 'credencial' => '', 'client_id' => '', 'client_secret' => null];
            }

            return ['ok' => true, 'errores' => [], 'aplica' => true, 'credencial' => $nombre, 'client_id' => $clientId, 'client_secret' => $guardado];
        }

        return [
            'ok'            => true,
            'errores'       => [],
            'aplica'        => true,
            'credencial'    => $nombre,
            'client_id'     => $clientId,
            'client_secret' => base64_encode(service('encrypter')->encrypt($clientSecret)),
        ];
    }

    private function leer(string $guardado): string
    {
        if ($guardado === '') {
            return '';
        }
        try {
            $claro = service('encrypter')->decrypt(base64_decode($guardado, true) ?: '');
        } catch (\Throwable) {
            return '';
        }

        return is_string($claro) ? $claro : '';
    }

    private function igual(string $esperado, string $presentado): bool
    {
        return $esperado !== '' && strlen($esperado) === strlen($presentado) && hash_equals($esperado, $presentado);
    }
}
