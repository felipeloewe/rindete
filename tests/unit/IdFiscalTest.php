<?php

declare(strict_types=1);

use App\Libraries\IdFiscal;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class IdFiscalTest extends CIUnitTestCase
{
    public function testAceptaLosEjemplosDelCatalogo(): void
    {
        $ejemplos = [
            'CL' => '12.345.678-5',
            'CO' => '900.123.456-8',
            'MX' => 'ABC010101XX4',
            'SV' => '0614-050707-104-8',
            'EC' => '1710034065001',
            'PE' => '20123456786',
        ];

        foreach ($ejemplos as $pais => $valor) {
            $resultado = IdFiscal::validar($pais, $valor);
            $this->assertTrue($resultado['ok'], $pais . ' ' . $resultado['error']);
            $this->assertNotSame('', $resultado['valor']);
        }
    }

    public function testRechazaUnRutConDigitoMal(): void
    {
        $resultado = IdFiscal::validar('CL', '12.345.678-4');

        $this->assertFalse($resultado['ok']);
    }

    public function testRechazaPaisDesconocido(): void
    {
        $resultado = IdFiscal::validar('AR', '20-12345678-9');

        $this->assertFalse($resultado['ok']);
    }
}
