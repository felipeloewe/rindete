<?php

declare(strict_types=1);

use App\Libraries\GastoServicio;
use App\Models\CategoriaModel;
use App\Models\EmpresaModel;
use App\Models\GastoModel;
use App\Models\UsuarioModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class GastoReevaluarTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testCrearDuplicadoSoloReescribeCandidatos(): void
    {
        [$usuario, $empresa, $categoria, $archivo] = $this->empresaConComprobante();
        $servicio                                  = new GastoServicio();

        for ($i = 0; $i < 12; $i++) {
            $ok = $servicio->crear($usuario, $empresa, $this->datosGasto($categoria, [
                'concepto'  => 'Otro ' . $i,
                'proveedor' => 'Proveedor ' . $i,
                'fecha'     => '2026-10-' . sprintf('%02d', $i + 1),
                'monto'     => (string) (5000 + $i),
                'num_doc'   => 'N-' . $i,
            ]), clone $archivo);
            $this->assertTrue($ok['ok'], implode(' ', $ok['errores']));
        }

        $ancla = $servicio->crear($usuario, $empresa, $this->datosGasto($categoria, [
            'concepto'  => 'Original',
            'proveedor' => 'Café Central',
            'fecha'     => '2026-11-09',
            'monto'     => '10000',
            'num_doc'   => 'DUP-1',
        ]), clone $archivo);
        $this->assertTrue($ancla['ok']);

        $idsAntes = array_map(
            static fn (array $g): int => (int) $g['id'],
            model(GastoModel::class)->where('empresa_id', $empresa['id'])->findAll(),
        );

        $dup = $servicio->crear($usuario, $empresa, $this->datosGasto($categoria, [
            'concepto'  => 'Copia',
            'proveedor' => 'Café Central',
            'fecha'     => '2026-11-10',
            'monto'     => '10000',
            'num_doc'   => 'DUP-2',
        ]), clone $archivo);
        $this->assertTrue($dup['ok']);
        $this->assertSame(1, (int) $dup['gasto']['duplicado']);

        $escrituras = $servicio->ultimasEscrituras();
        $this->assertNotEmpty($escrituras);
        $this->assertLessThanOrEqual(2, count($escrituras));
        $this->assertContains((int) $ancla['gasto']['id'], $escrituras);

        $ajenos = array_diff($idsAntes, [(int) $ancla['gasto']['id']]);
        foreach ($ajenos as $id) {
            $this->assertNotContains($id, $escrituras);
        }

        $par = model(GastoModel::class)->find($ancla['gasto']['id']);
        $this->assertSame(1, (int) $par['duplicado']);
        $this->assertSame('observado', $par['estado']);
    }

    public function testEliminarDuplicadoLimpiaAlPar(): void
    {
        [$usuario, $empresa, $categoria, $archivo] = $this->empresaConComprobante();
        $servicio                                  = new GastoServicio();

        $a = $servicio->crear($usuario, $empresa, $this->datosGasto($categoria, [
            'concepto'  => 'Uno',
            'proveedor' => 'Mismo',
            'fecha'     => '2026-11-09',
            'monto'     => '8000',
            'num_doc'   => 'A-1',
        ]), clone $archivo);
        $b = $servicio->crear($usuario, $empresa, $this->datosGasto($categoria, [
            'concepto'  => 'Dos',
            'proveedor' => 'Mismo',
            'fecha'     => '2026-11-10',
            'monto'     => '8000',
            'num_doc'   => 'A-2',
        ]), clone $archivo);
        $this->assertTrue($a['ok'] && $b['ok']);
        $this->assertSame(1, (int) model(GastoModel::class)->find($a['gasto']['id'])['duplicado']);

        $borrado = $servicio->eliminar($b['gasto']);
        $this->assertTrue($borrado['ok']);
        $this->assertSame([(int) $a['gasto']['id']], $servicio->ultimasEscrituras());

        $restante = model(GastoModel::class)->find($a['gasto']['id']);
        $this->assertSame(0, (int) $restante['duplicado']);
        $this->assertSame('legalizado', $restante['estado']);
    }

    public function testCambioDeTopeSoloTocaLaCategoria(): void
    {
        [$usuario, $empresa, $categoria, $archivo] = $this->empresaConComprobante();
        $otra = model(CategoriaModel::class)->where('empresa_id', $empresa['id'])->where('nombre !=', $categoria['nombre'])->first();
        model(CategoriaModel::class)->update($categoria['id'], ['tope' => 50000]);
        model(CategoriaModel::class)->update($otra['id'], ['tope' => 50000]);
        $servicio = new GastoServicio();

        $enTope = $servicio->crear($usuario, $empresa, $this->datosGasto($categoria, [
            'concepto'     => 'En cat',
            'proveedor'    => 'Local',
            'fecha'        => '2026-11-09',
            'monto'        => '40000',
            'num_doc'      => 'T-1',
            'categoria_id' => $categoria['id'],
        ]), clone $archivo);
        $otraCat = $servicio->crear($usuario, $empresa, $this->datosGasto($otra, [
            'concepto'     => 'Otra cat',
            'proveedor'    => 'Otro local',
            'fecha'        => '2026-11-09',
            'monto'        => '40000',
            'num_doc'      => 'T-2',
            'categoria_id' => $otra['id'],
        ]), clone $archivo);
        $this->assertTrue($enTope['ok'] && $otraCat['ok']);
        $this->assertSame(0, (int) $enTope['gasto']['fuera_politica']);
        $this->assertSame(0, (int) $otraCat['gasto']['fuera_politica']);

        model(CategoriaModel::class)->update($categoria['id'], ['tope' => 10000]);
        $escritas = $servicio->reevaluar((int) $empresa['id'], null, null, (int) $categoria['id']);
        $this->assertSame(1, $escritas);
        $this->assertSame([(int) $enTope['gasto']['id']], $servicio->ultimasEscrituras());

        $actualizado = model(GastoModel::class)->find($enTope['gasto']['id']);
        $intocado    = model(GastoModel::class)->find($otraCat['gasto']['id']);
        $this->assertSame(1, (int) $actualizado['fuera_politica']);
        $this->assertSame('observado', $actualizado['estado']);
        $this->assertSame(0, (int) $intocado['fuera_politica']);
        $this->assertSame('legalizado', $intocado['estado']);
    }

    public function testListadoDeGastosSePagina(): void
    {
        [$usuario, $empresa, $categoria, $archivo] = $this->empresaConComprobante();
        $servicio                                  = new GastoServicio();

        for ($i = 0; $i < 25; $i++) {
            $ok = $servicio->crear($usuario, $empresa, $this->datosGasto($categoria, [
                'concepto'  => 'Gasto pagina ' . $i,
                'proveedor' => 'Prov ' . $i,
                'fecha'     => '2026-09-' . sprintf('%02d', ($i % 28) + 1),
                'monto'     => (string) (3000 + $i),
                'num_doc'   => 'P-' . $i,
            ]), clone $archivo);
            $this->assertTrue($ok['ok'], implode(' ', $ok['errores']));
        }

        $pagina1 = $this->withSession(['usuario_id' => $usuario['id']])->get('gastos');
        $pagina1->assertOK();
        $pagina1->assertSee('Gasto pagina');
        $this->assertStringContainsString('paginacion', $pagina1->getBody());

        $pagina2 = $this->withSession(['usuario_id' => $usuario['id']])->get('gastos?page=2');
        $pagina2->assertOK();
        $pagina2->assertSee('Gasto pagina');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>, 3: UploadedFile}
     */
    private function empresaConComprobante(): array
    {
        $this->postForm('registro', [
            'pais'      => 'CL',
            'empresa'   => 'Acme SpA',
            'id_fiscal' => '12.345.678-5',
            'nombre'    => 'Ana Pérez',
            'email'     => 'ana@acme.test',
            'password'  => 'clave-segura',
        ]);
        $usuario   = model(UsuarioModel::class)->where('email', 'ana@acme.test')->first();
        $empresa   = model(EmpresaModel::class)->find($usuario['empresa_id']);
        $categoria = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();
        $ruta      = WRITEPATH . 'nota-reeval.pdf';
        file_put_contents($ruta, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");
        $archivo = new UploadedFile($ruta, 'nota.pdf', 'application/pdf', (int) filesize($ruta), UPLOAD_ERR_OK);

        return [$usuario, $empresa, $categoria, $archivo];
    }

    /**
     * @param array<string, mixed> $categoria
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    private function datosGasto(array $categoria, array $extra): array
    {
        return array_merge([
            'concepto'     => 'Gasto',
            'proveedor'    => 'Proveedor',
            'fecha'        => '2026-11-09',
            'monto'        => '10000',
            'categoria_id' => $categoria['id'],
            'tipo_doc'     => 'Boleta',
            'id_fiscal'    => '76.111.222-3',
            'num_doc'      => '1',
        ], $extra);
    }

    /**
     * @param array<string, string> $data
     */
    private function postForm(string $path, array $data)
    {
        $security = service('security');
        $data[$security->getTokenName()] = $security->getHash();

        return $this->post($path, $data);
    }
}
