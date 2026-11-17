<?php

declare(strict_types=1);

use App\Libraries\Planes;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PlanesTest extends CIUnitTestCase
{
    public function testElFreeEsCuatroUsuariosYDosGb(): void
    {
        $alta = Planes::alta();

        $this->assertSame('free', $alta['plan']);
        $this->assertSame(4, $alta['cupo_usuarios']);
        $this->assertSame(2 * 1024 * 1024 * 1024, $alta['cupo_bytes']);
        $this->assertSame('Plan Free. Hasta 4 usuarios y 2 GB.', Planes::texto());
    }

    public function testElCupoDeUsuariosYDeArchivos(): void
    {
        $this->assertTrue(Planes::puedeSumarUsuario(3));
        $this->assertFalse(Planes::puedeSumarUsuario(4));
        $this->assertTrue(Planes::cabe(100, 50, 150));
        $this->assertFalse(Planes::cabe(100, 51, 150));
        $this->assertTrue(Planes::cabe(100, 0, 150));
    }
}
