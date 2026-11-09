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
final class GastosTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testElAltaCreaLasCategorias(): void
    {
        $this->postForm('registro', $this->cuenta());

        $this->assertSame(6, model(CategoriaModel::class)->countAllResults());
    }

    public function testGuardarSinComprobanteQuedaObservado(): void
    {
        $this->postForm('registro', $this->cuenta());
        $usuario   = model(UsuarioModel::class)->where('email', 'ana@acme.test')->first();
        $categoria = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();

        $this->withSession(['usuario_id' => $usuario['id']]);
        $resultado = $this->postForm('gastos', [
            'concepto'     => 'Almuerzo',
            'proveedor'    => 'Café Central',
            'fecha'        => '2026-11-09',
            'monto'        => '10000',
            'categoria_id' => (string) $categoria['id'],
            'tipo_doc'     => 'Boleta',
            'id_fiscal'    => '76.111.222-3',
            'num_doc'      => '99',
        ]);

        $resultado->assertRedirect();
        $gasto = model(GastoModel::class)->first();
        $this->assertSame('observado', $gasto['estado']);
        $this->assertSame(1, (int) $gasto['sin_comprobante']);

        $lista = $this->withSession(['usuario_id' => $usuario['id']])->get('gastos');
        $lista->assertOK();
        $lista->assertSee('Almuerzo');
    }

    public function testUnComprobanteDentroDelTopeQuedaLegalizado(): void
    {
        $this->postForm('registro', $this->cuenta());
        $usuario   = model(UsuarioModel::class)->where('email', 'ana@acme.test')->first();
        $empresa   = model(EmpresaModel::class)->find($usuario['empresa_id']);
        $categoria = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();
        $ruta      = WRITEPATH . 'nota-prueba.pdf';
        file_put_contents($ruta, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");
        $archivo = new UploadedFile($ruta, 'nota.pdf', 'application/pdf', (int) filesize($ruta), UPLOAD_ERR_OK);

        $resultado = (new GastoServicio())->crear($usuario, $empresa, [
            'concepto'     => 'Taxi',
            'proveedor'    => 'Transporte Sur',
            'fecha'        => '2026-11-09',
            'monto'        => '12000',
            'categoria_id' => $categoria['id'],
            'tipo_doc'     => 'Boleta',
            'id_fiscal'    => '76.111.222-3',
            'num_doc'      => '100',
        ], $archivo);

        $this->assertTrue($resultado['ok'], implode(' ', $resultado['errores']));
        $this->assertSame('legalizado', $resultado['gasto']['estado']);
        $this->assertNotSame('', $resultado['gasto']['comprobante']);
        @unlink($ruta);
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

    /**
     * @return array<string, string>
     */
    private function cuenta(): array
    {
        return [
            'pais'      => 'CL',
            'empresa'   => 'Acme SpA',
            'id_fiscal' => '12.345.678-5',
            'nombre'    => 'Ana Pérez',
            'email'     => 'ana@acme.test',
            'password'  => 'clave-segura',
        ];
    }
}
