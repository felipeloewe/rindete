<?php

declare(strict_types=1);

use App\Libraries\GastoEvaluador;
use App\Libraries\Montos;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class GastoEvaluadorTest extends CIUnitTestCase
{
    public function testMarcaLegalizadoCuandoEstaCompleto(): void
    {
        $gasto = (new GastoEvaluador())->evaluar($this->gasto(), $this->empresa(), $this->categoria(45000), []);

        $this->assertSame('legalizado', $gasto['estado']);
        $this->assertSame(0, $gasto['sin_comprobante']);
        $this->assertSame(0, $gasto['fiscal_incompleto']);
    }

    public function testObservaSiSuperaElTopeOFaltaElComprobante(): void
    {
        $sobre = (new GastoEvaluador())->evaluar($this->gasto(['monto' => 50000]), $this->empresa(), $this->categoria(45000), []);
        $sin   = (new GastoEvaluador())->evaluar($this->gasto(['comprobante' => '']), $this->empresa(), $this->categoria(45000), []);

        $this->assertSame(1, $sobre['fuera_politica']);
        $this->assertSame('observado', $sobre['estado']);
        $this->assertSame(1, $sin['sin_comprobante']);
        $this->assertSame('observado', $sin['estado']);
    }

    public function testDetectaElMismoDocumento(): void
    {
        $otro  = $this->gasto(['id' => 8, 'num_doc' => 'F-10', 'id_fiscal' => '111-1']);
        $gasto = (new GastoEvaluador())->evaluar(
            $this->gasto(['num_doc' => 'f-10', 'id_fiscal' => '111-1']),
            $this->empresa(),
            $this->categoria(45000),
            [$otro],
        );

        $this->assertSame(1, $gasto['duplicado']);
        $this->assertSame('observado', $gasto['estado']);
    }

    public function testNoCambiaUnGastoYaAprobado(): void
    {
        $gasto = (new GastoEvaluador())->evaluar(
            $this->gasto(['estado' => 'aprobado', 'comprobante' => '']),
            $this->empresa(),
            $this->categoria(45000),
            [],
        );

        $this->assertSame('aprobado', $gasto['estado']);
        $this->assertSame(1, $gasto['sin_comprobante']);
    }

    public function testLeeMontosConSeparadorLatino(): void
    {
        $this->assertSame(12000.5, Montos::parse('12.000,50'));
        $this->assertSame(45000.0, Montos::parse('45000'));
        $this->assertNull(Montos::parse(''));
    }

    /**
     * @param array<string, mixed> $cambios
     *
     * @return array<string, mixed>
     */
    private function gasto(array $cambios = []): array
    {
        return array_merge([
            'concepto'    => 'Almuerzo',
            'proveedor'   => 'Café Central',
            'fecha'       => '2026-11-09',
            'monto'       => 10000,
            'tipo_doc'    => 'Boleta',
            'id_fiscal'   => '76.111.222-3',
            'num_doc'     => '99',
            'comprobante' => '1_abc.pdf',
            'estado'      => 'legalizado',
        ], $cambios);
    }

    /**
     * @return array<string, mixed>
     */
    private function empresa(): array
    {
        return ['detectar_duplicados' => 1, 'dias_duplicado' => 3, 'comprobante_desde' => 1];
    }

    /**
     * @return array<string, mixed>
     */
    private function categoria(float $tope): array
    {
        return ['tope' => $tope];
    }
}
