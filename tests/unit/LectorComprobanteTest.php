<?php

declare(strict_types=1);

use App\Libraries\LectorComprobante;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class LectorComprobanteTest extends CIUnitTestCase
{
    public function testDeTextoSacaFechaMontoRutYNumero(): void
    {
        $propuesta = (new LectorComprobante())->deTexto("RUT 76.111.222-3\nFecha 09/11/2026\nFolio 4412\nTotal 12.500");

        $this->assertSame('76.111.222-3', $propuesta['id_fiscal']);
        $this->assertSame('2026-11-09', $propuesta['fecha']);
        $this->assertSame('12500', $propuesta['monto']);
        $this->assertSame('4412', $propuesta['num_doc']);
        $this->assertSame('4412', $propuesta['codigo_fiscal']);
    }

    public function testDeTextoReconoceLaFacturaChilena(): void
    {
        $texto = "R.U.T.: 76111222-3\nFACTURA ELECTRONICA\nNº 4412\nFecha Emision: 9 de noviembre del 2026\nRazon social: Cafe Central\nMonto neto 10.000\nMonto total $ 12.500";
        $propuesta = (new LectorComprobante())->deTexto($texto);

        $this->assertSame('76.111.222-3', $propuesta['id_fiscal']);
        $this->assertSame('2026-11-09', $propuesta['fecha']);
        $this->assertSame('12500', $propuesta['monto']);
        $this->assertSame('4412', $propuesta['num_doc']);
        $this->assertSame('4412', $propuesta['codigo_fiscal']);
        $this->assertSame('Factura electrónica', $propuesta['tipo_doc']);
        $this->assertSame('Cafe Central', $propuesta['proveedor']);
    }

    public function testElXmlDelDteGanaAlTexto(): void
    {
        $xml = '<TipoDTE>33</TipoDTE><Folio>4412</Folio><FchEmis>2026-11-09</FchEmis>'
            . '<RUTEmisor>76111222-3</RUTEmisor><RznSoc>Cafe Central</RznSoc><MntTotal>12500</MntTotal>';
        $propuesta = (new LectorComprobante())->deDocumento("texto suelto\n" . $xml);

        $this->assertSame('76.111.222-3', $propuesta['id_fiscal']);
        $this->assertSame('4412', $propuesta['codigo_fiscal']);
        $this->assertSame('2026-11-09', $propuesta['fecha']);
        $this->assertSame('12500', $propuesta['monto']);
        $this->assertSame('Factura electrónica', $propuesta['tipo_doc']);
        $this->assertSame('Cafe Central', $propuesta['proveedor']);
    }

    public function testLeeElPdfAunqueElXmlVayaComprimido(): void
    {
        $xml = '<TipoDTE>33</TipoDTE><Folio>4412</Folio><FchEmis>2026-11-09</FchEmis>'
            . '<RUTEmisor>76111222-3</RUTEmisor><RznSoc>Cafe Central</RznSoc><MntTotal>12500</MntTotal>';
        $flujo = gzdeflate($xml);
        $pdf = "%PDF-1.4\n1 0 obj\n<< /Length " . strlen($flujo) . " /Filter /FlateDecode >>\nstream\n"
            . $flujo . "\nendstream\nendobj\ntrailer\n<<>>\n%%EOF\n";
        $ruta = WRITEPATH . 'factura-dte-test.pdf';
        file_put_contents($ruta, $pdf);

        $propuesta = (new LectorComprobante())->leerRuta($ruta, 'pdf');
        @unlink($ruta);

        $this->assertSame('76.111.222-3', $propuesta['id_fiscal']);
        $this->assertSame('4412', $propuesta['num_doc']);
        $this->assertSame('4412', $propuesta['codigo_fiscal']);
        $this->assertSame('2026-11-09', $propuesta['fecha']);
        $this->assertSame('12500', $propuesta['monto']);
        $this->assertSame('Factura electrónica', $propuesta['tipo_doc']);
        $this->assertSame('Cafe Central', $propuesta['proveedor']);
    }
}
