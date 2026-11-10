<?php

declare(strict_types=1);

use App\Libraries\Estados;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class EstadosTest extends CIUnitTestCase
{
    public function testAsignaUnColorPorEtapa(): void
    {
        $this->assertSame('chip chip-observado', Estados::clase('observado'));
        $this->assertSame('chip chip-legalizado', Estados::clase('legalizado'));
        $this->assertSame('chip chip-en_rendicion', Estados::clase('en_rendicion'));
        $this->assertSame('chip chip-aprobado', Estados::clase('aprobado'));
        $this->assertSame('chip chip-devuelto', Estados::clase('devuelto'));
        $this->assertSame('chip chip-legalizado', Estados::clase('otro'));
    }
}
