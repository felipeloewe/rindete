<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Lee una foto o el PDF de una factura electrónica y propone
 * proveedor, tipo, fecha, monto, RUT y folio.
 * Esos datos se guardan en el gasto y la persona los confirma.
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
        $origen = $archivo->getTempName();
        if ($origen === '' || ! is_file($origen)) {
            return [];
        }

        return $this->leerRuta($origen, strtolower($archivo->getExtension()));
    }

    /**
     * @return array<string, string>
     */
    public function leerRuta(string $ruta, string $ext = ''): array
    {
        if ($ruta === '' || ! is_file($ruta)) {
            return [];
        }
        if ($ext === '') {
            $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        }
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return $this->deDocumento($this->tesseract($ruta));
        }
        if ($ext === 'pdf') {
            return $this->deDocumento($this->textoPdf($ruta));
        }

        return [];
    }

    /**
     * @return array<string, string>
     */
    public function deDocumento(string $texto): array
    {
        if (trim($texto) === '') {
            return [];
        }
        $propuesta = $this->deTexto($texto);
        foreach ($this->deXml($texto) as $campo => $valor) {
            if ($valor !== '') {
                $propuesta[$campo] = $valor;
            }
        }

        return $propuesta;
    }

    /**
     * @return array<string, string>
     */
    public function deTexto(string $texto): array
    {
        $propuesta = [];
        if (preg_match('/(\d{1,2}\.?\d{3}\.?\d{3}-[\dkK])/', $texto, $coincidencia) === 1) {
            $propuesta['id_fiscal'] = $this->rut($coincidencia[1]);
        }
        if (preg_match('/(\d{1,2})\s+de\s+(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)\s+del?\s+(20\d{2})/iu', $texto, $coincidencia) === 1) {
            $meses = [
                'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5, 'junio' => 6,
                'julio' => 7, 'agosto' => 8, 'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10,
                'noviembre' => 11, 'diciembre' => 12,
            ];
            $mes = $meses[mb_strtolower($coincidencia[2])] ?? 0;
            if ($mes > 0) {
                $propuesta['fecha'] = sprintf('%04d-%02d-%02d', (int) $coincidencia[3], $mes, (int) $coincidencia[1]);
            }
        }
        if (! isset($propuesta['fecha']) && preg_match('/(\d{1,2})[\/\-.](\d{1,2})[\/\-.](20\d{2})/', $texto, $coincidencia) === 1) {
            $propuesta['fecha'] = sprintf('%04d-%02d-%02d', (int) $coincidencia[3], (int) $coincidencia[2], (int) $coincidencia[1]);
        } elseif (! isset($propuesta['fecha']) && preg_match('/(20\d{2})-(\d{2})-(\d{2})/', $texto, $coincidencia) === 1) {
            $propuesta['fecha'] = $coincidencia[0];
        }
        if (preg_match('/(?:monto\s+total|total\s+a\s+pagar)[^\d]{0,24}(\d{1,3}(?:\.\d{3})+|\d+)/iu', $texto, $coincidencia) === 1) {
            $propuesta['monto'] = str_replace('.', '', $coincidencia[1]);
        } elseif (preg_match('/(?:total|monto)[^\d]{0,16}(\d{1,3}(?:\.\d{3})+|\d+)/iu', $texto, $coincidencia) === 1) {
            $propuesta['monto'] = str_replace('.', '', $coincidencia[1]);
        }
        if (preg_match('/folio[^\d]{0,12}(\d{1,12})/iu', $texto, $coincidencia) === 1) {
            $propuesta['num_doc'] = $coincidencia[1];
            $propuesta['codigo_fiscal'] = $coincidencia[1];
        } elseif (preg_match('/(?:n[°ºo]\.?|nro\.?|numero)[^\d]{0,8}(\d{1,12})/iu', $texto, $coincidencia) === 1) {
            $propuesta['num_doc'] = $coincidencia[1];
        }
        if (preg_match('/nota\s+de\s+cr[eé]dito/iu', $texto) === 1) {
            $propuesta['tipo_doc'] = 'Nota de crédito electrónica';
        } elseif (preg_match('/factura\s+electr[oó]nica/iu', $texto) === 1) {
            $propuesta['tipo_doc'] = 'Factura electrónica';
        } elseif (preg_match('/boleta\s+electr[oó]nica/iu', $texto) === 1) {
            $propuesta['tipo_doc'] = 'Boleta electrónica';
        }
        if (isset($propuesta['tipo_doc'], $propuesta['num_doc']) && ! isset($propuesta['codigo_fiscal'])) {
            $propuesta['codigo_fiscal'] = $propuesta['num_doc'];
        }
        if (preg_match('/raz[oó]n\s+social[:\s]+([^\n]{3,140})/iu', $texto, $coincidencia) === 1) {
            $nombre = trim($coincidencia[1]);
            if ($nombre !== '') {
                $propuesta['proveedor'] = mb_substr($nombre, 0, 140);
            }
        }

        return $propuesta;
    }

    /**
     * @return array<string, string>
     */
    private function deXml(string $texto): array
    {
        $propuesta = [];
        if (preg_match('/<RUTEmisor>([^<]+)<\/RUTEmisor>/i', $texto, $coincidencia) === 1) {
            $propuesta['id_fiscal'] = $this->rut($coincidencia[1]);
        }
        if (preg_match('/<Folio>(\d+)<\/Folio>/i', $texto, $coincidencia) === 1) {
            $propuesta['num_doc']       = $coincidencia[1];
            $propuesta['codigo_fiscal'] = $coincidencia[1];
        }
        if (preg_match('/<FchEmis>(20\d{2}-\d{2}-\d{2})<\/FchEmis>/i', $texto, $coincidencia) === 1) {
            $propuesta['fecha'] = $coincidencia[1];
        }
        if (preg_match('/<MntTotal>(\d+)<\/MntTotal>/i', $texto, $coincidencia) === 1) {
            $propuesta['monto'] = $coincidencia[1];
        }
        if (preg_match('/<TipoDTE>(\d+)<\/TipoDTE>/i', $texto, $coincidencia) === 1) {
            $tipo = match ($coincidencia[1]) {
                '33', '34' => 'Factura electrónica',
                '39', '41' => 'Boleta electrónica',
                '61'       => 'Nota de crédito electrónica',
                default    => '',
            };
            if ($tipo !== '') {
                $propuesta['tipo_doc'] = $tipo;
            }
        }
        if (preg_match('/<RznSoc>([^<]+)<\/RznSoc>/i', $texto, $coincidencia) === 1) {
            $nombre = trim(html_entity_decode($coincidencia[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($nombre !== '') {
                $propuesta['proveedor'] = mb_substr($nombre, 0, 140);
            }
        }

        return $propuesta;
    }

    private function rut(string $raw): string
    {
        $limpio = strtoupper(str_replace('.', '', trim($raw)));
        if (preg_match('/^(\d{7,8})-([\dK])$/', $limpio, $coincidencia) !== 1) {
            return trim($raw);
        }
        $cuerpo = strrev(implode('.', str_split(strrev($coincidencia[1]), 3)));

        return $cuerpo . '-' . $coincidencia[2];
    }

    private function tesseract(string $ruta): string
    {
        if (! function_exists('exec')) {
            return '';
        }
        $base = tempnam(sys_get_temp_dir(), 'ocr');
        if ($base === false) {
            return '';
        }
        $comando = 'tesseract ' . escapeshellarg($ruta) . ' ' . escapeshellarg($base) . ' -l spa --psm 6';
        exec($comando, $salida, $codigo);
        $texto = is_file($base . '.txt') ? (string) file_get_contents($base . '.txt') : '';
        @unlink($base . '.txt');
        @unlink($base);

        return $codigo === 0 ? $texto : '';
    }

    private function textoPdf(string $ruta): string
    {
        $bytes  = (string) file_get_contents($ruta);
        $partes = [$this->pdftotext($ruta)];
        if (str_contains($bytes, '<RUTEmisor') || str_contains($bytes, '<Folio')) {
            $partes[] = $bytes;
        }
        foreach ($this->streams($bytes) as $stream) {
            if (str_contains($stream, '<RUTEmisor') || str_contains($stream, '<Folio')) {
                $partes[] = $stream;
            }
            if (str_contains($stream, 'Tj') || str_contains($stream, 'BT')) {
                $partes[] = $this->cadenas($stream);
            }
        }

        return trim(implode("\n", $partes));
    }

    /**
     * @return list<string>
     */
    private function streams(string $bytes): array
    {
        if (preg_match_all('/stream\r?\n(.*?)endstream/s', $bytes, $hallados) < 1) {
            return [];
        }
        $lista = [];
        foreach ($hallados[1] as $stream) {
            $lista[] = $this->inflar(rtrim($stream, "\r\n"));
        }

        return $lista;
    }

    private function cadenas(string $stream): string
    {
        if (preg_match_all('/\(((?:\\\\.|[^\\\\)])*)\)/s', $stream, $hallados) < 1) {
            return '';
        }
        $lineas = [];
        foreach ($hallados[1] as $cadena) {
            $linea = preg_replace_callback('/\\\\([0-7]{1,3})/', static fn (array $m): string => chr(octdec($m[1])), $cadena);
            $linea = str_replace(['\\(', '\\)', '\\\\', '\\n', '\\r'], ['(', ')', '\\', "\n", ''], (string) $linea);
            $linea = mb_convert_encoding($linea, 'UTF-8', 'ISO-8859-1');
            $linea = trim($linea);
            if ($linea !== '' && preg_match('/[\p{L}\d]/u', $linea) === 1) {
                $lineas[] = $linea;
            }
        }

        return implode("\n", $lineas);
    }

    private function inflar(string $stream): string
    {
        foreach (['gzuncompress', 'gzinflate', 'gzdecode'] as $funcion) {
            $inflado = @$funcion($stream);
            if (is_string($inflado) && $inflado !== '') {
                return $inflado;
            }
        }

        return $stream;
    }

    private function pdftotext(string $ruta): string
    {
        if (! function_exists('exec')) {
            return '';
        }
        $salida = [];
        $codigo = 1;
        exec('command -v pdftotext', $salida, $codigo);
        if ($codigo !== 0) {
            return '';
        }
        $base = tempnam(sys_get_temp_dir(), 'pdf');
        if ($base === false) {
            return '';
        }
        exec('pdftotext -layout ' . escapeshellarg($ruta) . ' ' . escapeshellarg($base), $salida, $codigo);
        $texto = is_file($base) ? (string) file_get_contents($base) : '';
        @unlink($base);

        return $codigo === 0 ? $texto : '';
    }
}
