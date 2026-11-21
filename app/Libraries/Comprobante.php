<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;

class Comprobante
{
    private const EXT = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'pdf'  => 'application/pdf',
    ];

    /**
     * @return array{ok: bool, nombre: string, error: string}
     */
    public function guardar(?UploadedFile $archivo, int $empresaId): array
    {
        if ($archivo === null || $archivo->getError() === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'nombre' => '', 'error' => ''];
        }
        if ($archivo->getError() !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'nombre' => '', 'error' => 'No se pudo subir el comprobante.'];
        }
        if ($archivo->getSize() > 5 * 1024 * 1024) {
            return ['ok' => false, 'nombre' => '', 'error' => 'El comprobante supera 5 MB.'];
        }

        $ext = strtolower($archivo->getExtension());
        if (! isset(self::EXT[$ext])) {
            return ['ok' => false, 'nombre' => '', 'error' => 'Usa JPG, PNG, WEBP o PDF.'];
        }
        $mime = (string) $archivo->getMimeType();
        $aceptados = [self::EXT[$ext], 'application/octet-stream'];
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $aceptados[] = 'image/jpg';
        }
        if ($mime !== '' && ! in_array($mime, $aceptados, true)) {
            return ['ok' => false, 'nombre' => '', 'error' => 'El archivo no coincide con su tipo.'];
        }

        $dir = $this->carpeta();
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return ['ok' => false, 'nombre' => '', 'error' => 'No se pudo guardar el comprobante.'];
        }
        $nombre = $empresaId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $origen = $archivo->getTempName();
        if (is_uploaded_file($origen)) {
            $archivo->move($dir, $nombre, true);
        } elseif (ENVIRONMENT === 'testing' && is_file($origen)) {
            copy($origen, $dir . DIRECTORY_SEPARATOR . $nombre);
        } else {
            return ['ok' => false, 'nombre' => '', 'error' => 'No se pudo guardar el comprobante.'];
        }

        return ['ok' => true, 'nombre' => $nombre, 'error' => ''];
    }

    /**
     * @return array{ok: bool, nombre: string, error: string}
     */
    public function guardarBytes(int $empresaId, string $bytes, string $ext): array
    {
        $ext = strtolower($ext);
        if (! isset(self::EXT[$ext]) || $bytes === '' || strlen($bytes) > 5 * 1024 * 1024) {
            return ['ok' => false, 'nombre' => '', 'error' => 'El comprobante no se pudo guardar.'];
        }
        $dir = $this->carpeta();
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return ['ok' => false, 'nombre' => '', 'error' => 'No se pudo guardar el comprobante.'];
        }
        $nombre = $empresaId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (file_put_contents($dir . $nombre, $bytes) === false) {
            return ['ok' => false, 'nombre' => '', 'error' => 'No se pudo guardar el comprobante.'];
        }

        return ['ok' => true, 'nombre' => $nombre, 'error' => ''];
    }

    public function usado(int $empresaId): int
    {
        $total = 0;
        $marca = '/^' . $empresaId . '_[a-f0-9]{16}\.(jpg|jpeg|png|webp|pdf)$/';
        foreach (glob($this->carpeta() . $empresaId . '_*') ?: [] as $ruta) {
            if (is_file($ruta) && preg_match($marca, basename($ruta)) === 1) {
                $total += (int) filesize($ruta);
            }
        }

        return $total;
    }

    public function tamano(string $nombre): int
    {
        $ruta = $this->ruta($nombre);

        return $ruta === null ? 0 : (int) filesize($ruta);
    }

    public function borrar(string $nombre): void
    {
        $ruta = $this->ruta($nombre);
        if ($ruta !== null) {
            unlink($ruta);
        }
    }

    public function ruta(string $nombre): ?string
    {
        if (preg_match('/^[0-9]+_[a-f0-9]{16}\.(jpg|jpeg|png|webp|pdf)$/', $nombre) !== 1) {
            return null;
        }
        $ruta = $this->carpeta() . $nombre;

        return is_file($ruta) ? $ruta : null;
    }

    private function carpeta(): string
    {
        return WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR;
    }

    public function mime(string $nombre): string
    {
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

        return self::EXT[$ext] ?? 'application/octet-stream';
    }
}
