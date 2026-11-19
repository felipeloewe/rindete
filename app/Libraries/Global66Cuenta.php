<?php

declare(strict_types=1);

namespace App\Libraries;

class Global66Cuenta
{
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
}
