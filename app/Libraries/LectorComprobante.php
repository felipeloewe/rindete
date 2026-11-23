<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Lee una foto del comprobante y propone fecha, monto, RUT y número.
 * El usuario confirma o corrige esos datos antes de dejar el gasto.
 */
class LectorComprobante
{
    /**
     * @return array<string, string>
     */
    public function leer(?UploadedFile $archivo): array
    {
        if ($archivo === null || $archivo->getError() !== UPLOAD_ERR_OK) {
            return [];
        }
        $ext = strtolower($archivo->getExtension());
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return [];
        }
        $origen = $archivo->getTempName();
        if ($origen === '' || ! is_file($origen)) {
            return [];
        }
        $base = tempnam(sys_get_temp_dir(), 'ocr');
        if ($base === false) {
            return [];
        }
        $comando = 'tesseract ' . escapeshellarg($origen) . ' ' . escapeshellarg($base) . ' -l spa --psm 6';
        exec($comando, $salida, $codigo);
        $texto = is_file($base . '.txt') ? (string) file_get_contents($base . '.txt') : '';
        @unlink($base . '.txt');
        @unlink($base);
        if ($codigo !== 0 || trim($texto) === '') {
            return [];
        }

        return $this->deTexto($texto);
    }

    /**
     * @return array<string, string>
     */
    public function leerRuta(string $ruta): array
    {
        if ($ruta === '' || ! is_file($ruta)) {
            return [];
        }
        $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return [];
        }
        $base = tempnam(sys_get_temp_dir(), 'ocr');
        if ($base === false) {
            return [];
        }
        $comando = 'tesseract ' . escapeshellarg($ruta) . ' ' . escapeshellarg($base) . ' -l spa --psm 6';
        exec($comando, $salida, $codigo);
        $texto = is_file($base . '.txt') ? (string) file_get_contents($base . '.txt') : '';
        @unlink($base . '.txt');
        @unlink($base);
        if ($codigo !== 0 || trim($texto) === '') {
            return [];
        }

        return $this->deTexto($texto);
    }

    /**
     * @return array<string, string>
     */
    public function deTexto(string $texto): array
    {
        $propuesta = [];
        if (preg_match('/(\d{1,2}\.?\d{3}\.?\d{3}-[\dkK])/', $texto, $coincidencia) === 1) {
            $propuesta['id_fiscal'] = $coincidencia[1];
        }
        if (preg_match('/(\d{2})[\/\-.](\d{2})[\/\-.](\d{4})/', $texto, $coincidencia) === 1) {
            $propuesta['fecha'] = $coincidencia[3] . '-' . $coincidencia[2] . '-' . $coincidencia[1];
        } elseif (preg_match('/(20\d{2})-(\d{2})-(\d{2})/', $texto, $coincidencia) === 1) {
            $propuesta['fecha'] = $coincidencia[0];
        }
        if (preg_match('/(?:total|monto)[^\d]{0,16}(\d{1,3}(?:\.\d{3})+|\d+)/iu', $texto, $coincidencia) === 1) {
            $propuesta['monto'] = str_replace('.', '', $coincidencia[1]);
        }
        if (preg_match('/(?:folio|n[°ºo]\.?|nro\.?|numero)[^\d]{0,8}(\d{1,12})/iu', $texto, $coincidencia) === 1) {
            $propuesta['num_doc'] = $coincidencia[1];
        }

        return $propuesta;
    }
}
