<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\GastoModel;

/**
 * El teléfono personal es el envío. El WhatsApp Business y el correo de la empresa son el receptor.
 * Ese par indica quién rinde y en qué empresa entra la boleta.
 */
class Recepcion
{
    /**
     * @param array<string, mixed> $cuerpo
     */
    public function whatsapp(array $cuerpo): void
    {
        $entradas = $cuerpo['entry'] ?? [];
        if (! is_array($entradas)) {
            return;
        }
        foreach ($entradas as $entrada) {
            if (! is_array($entrada)) {
                continue;
            }
            $cambios = $entrada['changes'] ?? [];
            if (! is_array($cambios)) {
                continue;
            }
            foreach ($cambios as $cambio) {
                if (! is_array($cambio)) {
                    continue;
                }
                $valor = $cambio['value'] ?? [];
                if (! is_array($valor)) {
                    continue;
                }
                $meta = $valor['metadata'] ?? [];
                $receptor = is_array($meta) ? (string) ($meta['display_phone_number'] ?? '') : '';
                $mensajes = $valor['messages'] ?? [];
                if (! is_array($mensajes)) {
                    continue;
                }
                foreach ($mensajes as $mensaje) {
                    if (is_array($mensaje)) {
                        $this->mensajeWhatsapp($receptor, $mensaje);
                    }
                }
            }
        }
    }

    public function correo(string $hacia, string $desde, string $texto, string $mensajeId, string $bytes, string $ext): bool
    {
        $identidad = new Identidad();
        $empresaId = $identidad->empresaPorCorreoBoletas($hacia);
        if ($empresaId === null) {
            return false;
        }
        $usuarioId = $identidad->usuarioPorCorreoEnEmpresa($empresaId, $desde);
        $nombre = $this->archivo($empresaId, $bytes, $ext);

        $this->guardar($empresaId, $usuarioId, 'correo', Identidad::correo($desde), Identidad::correo($hacia), $mensajeId, $texto, $nombre, $this->lectura($nombre));

        return true;
    }

    /**
     * @param array<string, mixed> $usuario
     *
     * @return list<array<string, mixed>>
     */
    public function pendientes(array $usuario): array
    {
        $consulta = db_connect()->table('entradas')
            ->where('empresa_id', (int) $usuario['empresa_id'])
            ->where('estado', 'pendiente');
        if (($usuario['rol'] ?? '') === 'administrador') {
            $consulta->groupStart()
                ->where('usuario_id', (int) $usuario['id'])
                ->orWhere('usuario_id', null)
                ->groupEnd();
        } else {
            $consulta->where('usuario_id', (int) $usuario['id']);
        }

        return $consulta->orderBy('id', 'DESC')->get()->getResultArray();
    }

    /**
     * @param array<string, mixed> $usuario
     *
     * @return array<string, mixed>|null
     */
    public function visible(array $usuario, int $id): ?array
    {
        $fila = db_connect()->table('entradas')->where('id', $id)->get()->getRowArray();
        if ($fila === null || (int) $fila['empresa_id'] !== (int) $usuario['empresa_id'] || (string) $fila['estado'] !== 'pendiente') {
            return null;
        }
        $dueno = $fila['usuario_id'] === null ? 0 : (int) $fila['usuario_id'];
        if ($dueno === (int) $usuario['id']) {
            return $fila;
        }

        return ($usuario['rol'] ?? '') === 'administrador' && $dueno === 0 ? $fila : null;
    }

    /**
     * @param array<string, mixed> $usuario
     *
     * @return array<string, mixed>|null
     */
    public function paraGasto(array $usuario, int $id): ?array
    {
        $fila = $this->visible($usuario, $id);
        if ($fila === null || (int) ($fila['usuario_id'] ?? 0) !== (int) $usuario['id']) {
            return null;
        }

        return $fila;
    }

    /**
     * @param array<string, mixed> $usuario
     * @param array<string, mixed> $gasto
     */
    public function adjuntar(array $usuario, int $entradaId, array $gasto): void
    {
        $entrada = $this->paraGasto($usuario, $entradaId);
        if ($entrada === null) {
            return;
        }
        if ((string) ($gasto['comprobante'] ?? '') === '' && (string) ($entrada['comprobante'] ?? '') !== '') {
            model(GastoModel::class)->update($gasto['id'], ['comprobante' => $entrada['comprobante']]);
            (new GastoServicio())->reevaluar((int) $usuario['empresa_id']);
        }
        db_connect()->table('entradas')->where('id', $entradaId)->update(['estado' => 'tomada']);
    }

    /**
     * @param array<string, mixed> $usuario
     */
    public function tomar(array $usuario, int $id): bool
    {
        $entrada = $this->visible($usuario, $id);
        if ($entrada === null) {
            return false;
        }
        db_connect()->table('entradas')->where('id', $id)->update(['estado' => 'tomada']);

        return true;
    }

    /**
     * @param array<string, mixed> $mensaje
     */
    private function mensajeWhatsapp(string $receptor, array $mensaje): void
    {
        $identidad = new Identidad();
        $empresaId = $identidad->empresaPorWhatsapp($receptor);
        if ($empresaId === null) {
            return;
        }
        $emisor = (string) ($mensaje['from'] ?? '');
        $usuarioId = $identidad->usuarioPorTelefono($empresaId, $emisor);
        $texto = '';
        $mediaId = '';
        $mime = '';
        $tipo = (string) ($mensaje['type'] ?? '');
        if ($tipo === 'text' && is_array($mensaje['text'] ?? null)) {
            $texto = (string) ($mensaje['text']['body'] ?? '');
        }
        foreach (['image', 'document'] as $parte) {
            if (! is_array($mensaje[$parte] ?? null)) {
                continue;
            }
            $texto = (string) ($mensaje[$parte]['caption'] ?? $texto);
            $mediaId = (string) ($mensaje[$parte]['id'] ?? '');
            $mime = (string) ($mensaje[$parte]['mime_type'] ?? '');
        }
        $nombre = $this->bajarWhatsapp($empresaId, $mediaId, $mime);
        $this->guardar(
            $empresaId,
            $usuarioId,
            'whatsapp',
            Identidad::telefono($emisor),
            Identidad::telefono($receptor),
            (string) ($mensaje['id'] ?? ''),
            $texto,
            $nombre,
            $this->lectura($nombre),
        );
    }

    private function guardar(int $empresaId, ?int $usuarioId, string $canal, string $emisor, string $receptor, string $mensajeId, string $texto, string $comprobante, string $lectura = ''): void
    {
        $mensajeId = trim($mensajeId);
        if ($mensajeId !== '') {
            $ya = db_connect()->table('entradas')->where('mensaje_id', $mensajeId)->countAllResults();
            if ($ya > 0) {
                return;
            }
        }
        db_connect()->table('entradas')->insert([
            'empresa_id'  => $empresaId,
            'usuario_id'  => $usuarioId,
            'canal'       => $canal,
            'emisor'      => mb_substr($emisor, 0, 160),
            'receptor'    => mb_substr($receptor, 0, 160),
            'mensaje_id'  => $mensajeId === '' ? null : mb_substr($mensajeId, 0, 191),
            'texto'       => mb_substr($texto, 0, 2000),
            'comprobante' => $comprobante,
            'lectura'     => $lectura === '' ? null : $lectura,
            'estado'      => 'pendiente',
            'creado'      => date('Y-m-d H:i:s'),
        ]);
    }

    private function lectura(string $nombre): string
    {
        if ($nombre === '') {
            return '';
        }
        $ruta = (new Comprobante())->ruta($nombre);
        if ($ruta === null) {
            return '';
        }
        $propuesta = (new LectorComprobante())->leerRuta($ruta);

        return $propuesta === [] ? '' : (string) json_encode($propuesta, JSON_UNESCAPED_UNICODE);
    }

    private function archivo(int $empresaId, string $bytes, string $ext): string
    {
        if ($bytes === '') {
            return '';
        }
        $guardado = (new Comprobante())->guardarBytes($empresaId, $bytes, $ext);

        return $guardado['ok'] ? $guardado['nombre'] : '';
    }

    private function bajarWhatsapp(int $empresaId, string $mediaId, string $mime): string
    {
        $token = $this->token('whatsapp.token');
        if ($token === '' || $mediaId === '' || ! preg_match('/^[A-Za-z0-9_-]+$/', $mediaId)) {
            return '';
        }
        try {
            $meta = $this->pedir('https://graph.facebook.com/v21.0/' . rawurlencode($mediaId), $token);
            $url = is_array($meta) ? (string) ($meta['url'] ?? '') : '';
            if ($url === '' || ! str_starts_with($url, 'https://')) {
                return '';
            }
            $binario = $this->pedirBinario($url, $token);
            $ext = $this->extension($mime !== '' ? $mime : (string) ($meta['mime_type'] ?? ''));
            if ($binario === '' || $ext === '') {
                return '';
            }

            return $this->archivo($empresaId, $binario, $ext);
        } catch (\Throwable) {
            return '';
        }
    }

    private function extension(string $mime): string
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => '',
        };
    }

    private function token(string $clave): string
    {
        $valor = getenv($clave);
        if (is_string($valor) && $valor !== '') {
            return $valor;
        }
        $deEntorno = env($clave);

        return is_string($deEntorno) ? $deEntorno : '';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function pedir(string $url, string $token): ?array
    {
        $cuerpo = $this->pedirBinario($url, $token);
        $json = json_decode($cuerpo, true);

        return is_array($json) ? $json : null;
    }

    private function pedirBinario(string $url, string $token): string
    {
        $contexto = stream_context_create([
            'http' => [
                'header'  => "Authorization: Bearer {$token}\r\n",
                'timeout' => 8,
            ],
        ]);
        $cuerpo = file_get_contents($url, false, $contexto);

        return is_string($cuerpo) ? $cuerpo : '';
    }
}
