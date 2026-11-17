<?php

declare(strict_types=1);

use App\Libraries\GastoServicio;
use App\Libraries\RendicionServicio;
use App\Models\CategoriaModel;
use App\Models\EmpresaModel;
use App\Models\GastoModel;
use App\Models\RendicionModel;
use App\Models\UsuarioModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class RendicionesTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testSoloEntraUnGastoLegalizadoYEnviarLoCierra(): void
    {
        $this->postForm('registro', $this->cuenta());
        $usuario   = model(UsuarioModel::class)->where('email', 'ana@acme.test')->first();
        $empresa   = model(EmpresaModel::class)->find($usuario['empresa_id']);
        $categoria = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();
        $servicio  = new RendicionServicio();
        $creada    = $servicio->crear($usuario, 'Viaje de noviembre', 'Cliente en Santiago');
        $rendicion = model(RendicionModel::class)->find($creada['id']);

        $observado = (new GastoServicio())->crear($usuario, $empresa, [
            'concepto'     => 'Cena',
            'proveedor'    => 'Local Norte',
            'fecha'        => '2026-11-09',
            'monto'        => '80000',
            'categoria_id' => $categoria['id'],
        ], null);
        $legal = $this->legalizado($usuario, $empresa, $categoria);

        $this->assertFalse($servicio->agregar($rendicion, $observado['gasto'])['ok']);
        $this->assertTrue($servicio->agregar($rendicion, $legal['gasto'])['ok']);
        $gasto = model(GastoModel::class)->find($legal['gasto']['id']);
        $this->assertSame('en_rendicion', $gasto['estado']);
        $this->assertSame((int) $rendicion['id'], (int) $gasto['rendicion_id']);

        $this->withSession(['usuario_id' => $usuario['id']]);
        $vacio = $servicio->enviar(model(RendicionModel::class)->find($rendicion['id']));
        $this->assertTrue($vacio['ok']);
        $this->assertSame('enviada', model(RendicionModel::class)->find($rendicion['id'])['estado']);
        $this->assertFalse($servicio->agregar(model(RendicionModel::class)->find($rendicion['id']), $legal['gasto'])['ok']);

        $pagina = $this->get('rendiciones/' . $rendicion['id']);
        $pagina->assertOK();
        $pagina->assertSee('Viaje de noviembre');
        $pagina->assertSee('Enviada');
    }

    public function testQuitarDevuelveElGastoALegalizado(): void
    {
        $this->postForm('registro', $this->cuenta());
        $usuario   = model(UsuarioModel::class)->where('email', 'ana@acme.test')->first();
        $empresa   = model(EmpresaModel::class)->find($usuario['empresa_id']);
        $categoria = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();
        $servicio  = new RendicionServicio();
        $creada    = $servicio->crear($usuario, 'Corta', '');
        $rendicion = model(RendicionModel::class)->find($creada['id']);
        $legal     = $this->legalizado($usuario, $empresa, $categoria);
        $servicio->agregar($rendicion, $legal['gasto']);

        $this->assertTrue($servicio->quitar($rendicion, model(GastoModel::class)->find($legal['gasto']['id']))['ok']);
        $gasto = model(GastoModel::class)->find($legal['gasto']['id']);
        $this->assertSame('legalizado', $gasto['estado']);
        $this->assertNull($gasto['rendicion_id']);
        $this->assertFalse($servicio->enviar($rendicion)['ok']);
    }

    /**
     * @param array<string, mixed> $usuario
     * @param array<string, mixed> $empresa
     * @param array<string, mixed> $categoria
     *
     * @return array{ok: bool, errores: list<string>, gasto?: array<string, mixed>}
     */
    private function legalizado(array $usuario, array $empresa, array $categoria): array
    {
        $ruta = WRITEPATH . 'rendicion-prueba.pdf';
        file_put_contents($ruta, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");
        $archivo = new UploadedFile($ruta, 'nota.pdf', 'application/pdf', (int) filesize($ruta), UPLOAD_ERR_OK);
        $resultado = (new GastoServicio())->crear($usuario, $empresa, [
            'concepto'     => 'Taxi',
            'proveedor'    => 'Transporte Sur',
            'fecha'        => '2026-11-10',
            'monto'        => '12000',
            'categoria_id' => $categoria['id'],
            'tipo_doc'     => 'Boleta',
            'id_fiscal'    => '76.111.222-3',
            'num_doc'      => '100',
        ], $archivo);
        @unlink($ruta);

        return $resultado;
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
