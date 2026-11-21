<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Excel y PDF de una o más tablas, con el mismo texto que el informe.
 */
class ArchivoHoja
{
    /**
     * @param list<array{titulo: string, headers: list<string>, filas: list<list<string>>}> $tablas
     */
    public static function excel(string $titulo, array $tablas): string
    {
        $filas = [];
        $filas[] = [$titulo];
        foreach ($tablas as $tabla) {
            $filas[] = [];
            if ($tabla['titulo'] !== '') {
                $filas[] = [$tabla['titulo']];
            }
            $filas[] = $tabla['headers'];
            foreach ($tabla['filas'] as $fila) {
                $filas[] = $fila;
            }
        }
        if ($filas === [[$titulo]]) {
            $filas[] = ['Sin movimientos en este período'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach ($filas as $i => $fila) {
            $n = $i + 1;
            $xml .= '<row r="' . $n . '">';
            foreach (array_values($fila) as $c => $valor) {
                $ref = self::columna($c) . $n;
                $texto = htmlspecialchars((string) $valor, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . $texto . '</t></is></c>';
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData></worksheet>';

        $tmp = tempnam(sys_get_temp_dir(), 'rindete-xlsx');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Informe" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
        $zip->close();
        $binario = (string) file_get_contents($tmp);
        unlink($tmp);

        return $binario;
    }

    /**
     * @param list<array{titulo: string, headers: list<string>, filas: list<list<string>>}> $tablas
     */
    public static function pdf(string $titulo, array $tablas): string
    {
        $lineas = [self::win($titulo), ''];
        foreach ($tablas as $tabla) {
            if ($tabla['titulo'] !== '') {
                $lineas[] = self::win($tabla['titulo']);
            }
            $lineas[] = self::win(implode(' | ', $tabla['headers']));
            if ($tabla['filas'] === []) {
                $lineas[] = self::win('Sin movimientos en este período');
            }
            foreach ($tabla['filas'] as $fila) {
                $lineas[] = self::win(implode(' | ', $fila));
            }
            $lineas[] = '';
        }
        $paginas = array_chunk($lineas, 42);
        if ($paginas === []) {
            $paginas = [['']];
        }

        $objetos = [];
        $objetos[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        $fuente = 3;
        $objetos[$fuente] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $siguiente = 4;
        foreach ($paginas as $pagina) {
            $contenido = "BT\n/F1 9 Tf\n36 800 Td\n14 TL\n";
            foreach ($pagina as $linea) {
                $contenido .= '(' . $linea . ") '\n";
            }
            $contenido .= "ET\n";
            $idContenido = $siguiente++;
            $idPagina = $siguiente++;
            $objetos[$idContenido] = '<< /Length ' . strlen($contenido) . " >>\nstream\n" . $contenido . "endstream";
            $objetos[$idPagina] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents ' . $idContenido
                . ' 0 R /Resources << /Font << /F1 ' . $fuente . ' 0 R >> >> >>';
            $kids[] = $idPagina . ' 0 R';
        }
        $objetos[2] = '<< /Type /Pages /Count ' . count($kids) . ' /Kids [' . implode(' ', $kids) . '] >>';
        ksort($objetos);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objetos as $id => $cuerpo) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $cuerpo . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $total = max(array_keys($objetos)) + 1;
        $pdf .= "xref\n0 $total\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $total; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size $total /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";

        return $pdf;
    }

    private static function columna(int $indice): string
    {
        $letras = '';
        $n = $indice;
        do {
            $letras = chr(65 + ($n % 26)) . $letras;
            $n = intdiv($n, 26) - 1;
        } while ($n >= 0);

        return $letras;
    }

    private static function win(string $texto): string
    {
        $texto = str_replace(["\r", "\n"], ' ', $texto);
        $plano = iconv('UTF-8', 'Windows-1252//TRANSLIT', $texto);
        if ($plano === false) {
            $plano = preg_replace('/[^\x20-\x7E]/', ' ', $texto) ?? '';
        }

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $plano);
    }
}
