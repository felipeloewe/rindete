<?php

declare(strict_types=1);

namespace App\Libraries;

class Global66Cuenta
{
    private const BASE = 'https://api.global66.com/business-api';

    /** @param (callable(string, string, ?string, ?string): array{codigo: int, json: ?array})|null $transporte */
    public function __construct(private $transporte = null)
    {
    }

    /**
     * Pide un token con el Client ID y el Client Secret y lee las cuentas de la empresa.
     *
     * @return array{ok: bool, errores: list<string>, token: string, cuentas: list<array{walletId: string, currency: string}>}
     */
    public function probar(string $clientId, string $clientSecret): array
    {
        $clientId = trim($clientId);
        $clientSecret = trim($clientSecret);
        $vacio = ['ok' => false, 'errores' => ['Falta el Client ID o el Client Secret de Global66.'], 'token' => '', 'cuentas' => []];
        if ($clientId === '' || $clientSecret === '') {
            return $vacio;
        }
        $auth = $this->enviar('POST', '/b2b/auth', json_encode(['clientId' => $clientId, 'clientSecret' => $clientSecret]), null);
        if ($auth['codigo'] === 401) {
            return ['ok' => false, 'errores' => ['Global66 rechazó el Client ID o el Client Secret.'], 'token' => '', 'cuentas' => []];
        }
        if ($auth['codigo'] === 403) {
            return ['ok' => false, 'errores' => ['Global66 rechazó la IP de salida. Revísala en la credencial.'], 'token' => '', 'cuentas' => []];
        }
        $token = is_array($auth['json']) ? trim((string) ($auth['json']['token'] ?? '')) : '';
        if ($auth['codigo'] < 200 || $auth['codigo'] >= 300 || $token === '') {
            return ['ok' => false, 'errores' => ['Global66 no aceptó la credencial.'], 'token' => '', 'cuentas' => []];
        }
        $lista = $this->enviar('GET', '/b2b/accounts', null, $token);
        if ($lista['codigo'] === 401) {
            return ['ok' => false, 'errores' => ['Global66 rechazó el Client ID o el Client Secret.'], 'token' => '', 'cuentas' => []];
        }
        if ($lista['codigo'] === 403) {
            return ['ok' => false, 'errores' => ['Global66 rechazó la IP de salida. Revísala en la credencial.'], 'token' => '', 'cuentas' => []];
        }
        if ($lista['codigo'] < 200 || $lista['codigo'] >= 300 || ! is_array($lista['json'])) {
            return ['ok' => false, 'errores' => ['Global66 no entregó las cuentas de la empresa.'], 'token' => '', 'cuentas' => []];
        }
        $cuentas = [];
        foreach ($lista['json']['accounts'] ?? [] as $cuenta) {
            if (! is_array($cuenta) || ! isset($cuenta['walletId'])) {
                continue;
            }
            $cuentas[] = [
                'walletId' => (string) $cuenta['walletId'],
                'currency' => strtoupper(substr((string) ($cuenta['currency'] ?? ''), 0, 3)),
            ];
        }

        return ['ok' => true, 'errores' => [], 'token' => $token, 'cuentas' => $cuentas];
    }

    /**
     * @return array{ok: bool, errores: list<string>, movements: list<array<string, mixed>>, totalPages: int}
     */
    public function consultar(string $token, string $billetera): array
    {
        $token = trim($token);
        if ($token === '' || preg_match('/^\d+$/', $billetera) !== 1) {
            return ['ok' => false, 'errores' => ['Global66 no entregó los movimientos de la cuenta.'], 'movements' => [], 'totalPages' => 0];
        }
        $todos = [];
        $paginas = 1;
        for ($pagina = 1; $pagina <= 3 && $pagina <= $paginas; $pagina++) {
            $respuesta = $this->pagina($token, $billetera, $pagina);
            if (! $respuesta['ok']) {
                return $respuesta;
            }
            $todos = array_merge($todos, $respuesta['movements']);
            $paginas = $respuesta['totalPages'];
        }

        return ['ok' => true, 'errores' => [], 'movements' => $todos, 'totalPages' => $paginas];
    }

    /**
     * @return array{ok: bool, errores: list<string>, movements: list<array<string, mixed>>, totalPages: int}
     */
    private function pagina(string $token, string $billetera, int $pagina): array
    {
        $url = 'https://api.global66.com/business-api/b2b/movements/' . rawurlencode($billetera) . '?page=' . $pagina;
        $canal = curl_init($url);
        if ($canal === false) {
            return ['ok' => false, 'errores' => ['Global66 no entregó los movimientos de la cuenta.'], 'movements' => [], 'totalPages' => 0];
        }
        curl_setopt_array($canal, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => ['Authorization: ' . $token, 'Accept: application/json'],
        ]);
        $cuerpo = curl_exec($canal);
        $codigo = (int) curl_getinfo($canal, CURLINFO_HTTP_CODE);
        curl_close($canal);
        $json = is_string($cuerpo) ? json_decode($cuerpo, true) : null;
        if ($codigo < 200 || $codigo >= 300 || ! is_array($json)) {
            return ['ok' => false, 'errores' => ['Global66 no entregó los movimientos de la cuenta.'], 'movements' => [], 'totalPages' => 0];
        }
        $movements = [];
        foreach ($json['movements'] ?? [] as $fila) {
            if (is_array($fila)) {
                $movements[] = $fila;
            }
        }

        return [
            'ok'         => true,
            'errores'    => [],
            'movements'  => $movements,
            'totalPages' => max(1, (int) ($json['totalPages'] ?? 1)),
        ];
    }

    /**
     * @return array{codigo: int, json: ?array<string, mixed>}
     */
    private function enviar(string $metodo, string $ruta, ?string $cuerpo, ?string $token): array
    {
        if ($this->transporte !== null) {
            return ($this->transporte)($metodo, $ruta, $cuerpo, $token);
        }
        $canal = curl_init(self::BASE . $ruta);
        if ($canal === false) {
            return ['codigo' => 0, 'json' => null];
        }
        $cabeceras = ['Accept: application/json'];
        if ($cuerpo !== null) {
            $cabeceras[] = 'Content-Type: application/json';
        }
        if ($token !== null && $token !== '') {
            $cabeceras[] = 'Authorization: ' . $token;
        }
        curl_setopt_array($canal, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CUSTOMREQUEST  => $metodo,
            CURLOPT_HTTPHEADER     => $cabeceras,
        ]);
        if ($cuerpo !== null) {
            curl_setopt($canal, CURLOPT_POSTFIELDS, $cuerpo);
        }
        $respuesta = curl_exec($canal);
        $codigo = (int) curl_getinfo($canal, CURLINFO_HTTP_CODE);
        curl_close($canal);
        $json = is_string($respuesta) ? json_decode($respuesta, true) : null;

        return ['codigo' => $codigo, 'json' => is_array($json) ? $json : null];
    }
}
