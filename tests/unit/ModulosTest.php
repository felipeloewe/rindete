<?php

declare(strict_types=1);

use App\Libraries\Modulos;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ModulosTest extends CIUnitTestCase
{
    public function testLoGratuitoEstaSiempreYLoDesconocidoNo(): void
    {
        $empresa = ['id' => 1, 'plan' => 'free'];

        $this->assertTrue(Modulos::activo($empresa, 'gastos'));
        $this->assertTrue(Modulos::activo($empresa, 'fondos'));
        $this->assertFalse(Modulos::activo($empresa, 'inexistente'));
        $this->assertArrayHasKey('preasientos', Modulos::CATALOGO);
        $this->assertArrayHasKey('asientos', Modulos::CATALOGO);
        $this->assertArrayHasKey('cuentas', Modulos::CATALOGO);
        $this->assertStringContainsString('Free', Modulos::aviso('preasientos'));
        $this->assertStringContainsString('contratan', Modulos::resumen());
    }
}
