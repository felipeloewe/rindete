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
    }
}
