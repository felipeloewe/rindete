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
    public function guardar(int $empresaId, string $proveedor, string $secreto, string $tokenConsulta): array
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
        }
        if (strlen($tokenConsulta) > 500) {
            return ['ok' => false, 'errores' => ['El token de consulta de Global66 no cabe.']];
        }
        $cifrado = base64_encode(service('encrypter')->encrypt($secreto));
        $consulta = $tokenConsulta === '' ? null : base64_encode(service('encrypter')->encrypt($tokenConsulta));
        $modelo = model(MedioModel::class);
        $actual = $modelo->where('empresa_id', $empresaId)->where('proveedor', $proveedor)->first();
        if (is_array($actual)) {
            $modelo->update($actual['id'], [
                'secreto'        => $cifrado,
                'token_consulta' => $consulta,
                'vigente'        => 1,
            ]);
        } else {
            $modelo->insert([
                'empresa_id'     => $empresaId,
                'proveedor'      => $proveedor,
                'clave'          => bin2hex(random_bytes(16)),
                'secreto'        => $cifrado,
                'token_consulta' => $consulta,
                'vigente'        => 1,
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

    public function url(array $medio): string
    {
        return site_url('webhooks/' . $medio['proveedor'] . '/' . $medio['clave']);
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
