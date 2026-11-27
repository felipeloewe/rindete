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
        $usuario   = $this->rendidora(model(UsuarioModel::class)->porEmail('ana@acme.test'));
        $categoria = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();

        $this->withSession(['usuario_id' => $usuario['id']]);
        $resultado = $this->postForm('gastos', [
            'concepto'     => 'Almuerzo',
            'proveedor'    => 'Café Central',
            'fecha'        => '2026-11-09',
            'monto'        => '10000',
            'categoria_id' => (string) $categoria['id'],
            'tipo_doc'     => 'Boleta electrónica',
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
        $usuario   = $this->rendidora(model(UsuarioModel::class)->porEmail('ana@acme.test'));
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
            'tipo_doc'     => 'Boleta electrónica',
            'id_fiscal'    => '76.111.222-3',
            'num_doc'      => '100',
        ], $archivo);

        $this->assertTrue($resultado['ok'], implode(' ', $resultado['errores']));
        $this->assertSame('legalizado', $resultado['gasto']['estado']);
        $this->assertNotSame('', $resultado['gasto']['comprobante']);
        @unlink($ruta);
    }

    public function testLaFacturaElectronicaGuardaLosDatosLeidos(): void
    {
        $this->postForm('registro', $this->cuenta());
        $usuario   = $this->rendidora(model(UsuarioModel::class)->porEmail('ana@acme.test'));
        $categoria = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();
        $xml       = '<TipoDTE>33</TipoDTE><Folio>4412</Folio><FchEmis>2026-11-09</FchEmis>'
            . '<RUTEmisor>76111222-3</RUTEmisor><RznSoc>Cafe Central</RznSoc><MntTotal>12500</MntTotal>';
        $flujo = gzdeflate($xml);
        $pdf   = "%PDF-1.4\n1 0 obj\n<< /Length " . strlen($flujo) . " /Filter /FlateDecode >>\nstream\n"
            . $flujo . "\nendstream\nendobj\ntrailer\n<<>>\n%%EOF\n";
        $ruta = WRITEPATH . 'factura-formulario.pdf';
        file_put_contents($ruta, $pdf);
        service('superglobals')->setFilesArray([
            'comprobante' => [
                'name'     => 'factura.pdf',
                'type'     => 'application/pdf',
                'tmp_name' => $ruta,
                'error'    => UPLOAD_ERR_OK,
                'size'     => filesize($ruta),
            ],
        ]);

        $resultado = $this->withSession(['usuario_id' => $usuario['id']])->postForm('gastos', [
            'concepto'     => 'Almuerzo',
            'proveedor'    => '',
            'fecha'        => date('Y-m-d'),
            'monto'        => '',
            'categoria_id' => (string) $categoria['id'],
            'tipo_doc'     => '',
            'id_fiscal'    => '',
            'num_doc'      => '',
            'codigo_fiscal'=> '',
        ]);

        service('superglobals')->setFilesArray([]);
        @unlink($ruta);
        $resultado->assertRedirect();
        $gasto = model(GastoModel::class)->orderBy('id', 'DESC')->first();
        $this->assertSame('Cafe Central', $gasto['proveedor']);
        $this->assertSame('Factura electrónica', $gasto['tipo_doc']);
        $this->assertSame('76.111.222-3', $gasto['id_fiscal']);
        $this->assertSame('4412', $gasto['num_doc']);
        $this->assertSame('4412', $gasto['codigo_fiscal']);
        $this->assertSame('2026-11-09', $gasto['fecha']);
        $this->assertSame(12500.0, (float) $gasto['monto']);
        $this->assertSame('legalizado', $gasto['estado']);

        $editar = $this->withSession(['usuario_id' => $usuario['id']])->get('gastos/' . $gasto['id'] . '/editar');
        $editar->assertOK();
        $cuerpo = $editar->getBody();
        $this->assertStringContainsString('value="Cafe Central"', $cuerpo);
        $this->assertStringContainsString('value="76.111.222-3"', $cuerpo);
        $this->assertStringContainsString('value="4412"', $cuerpo);
        $this->assertStringContainsString('value="2026-11-09"', $cuerpo);
        $this->assertStringContainsString('value="Factura electr&oacute;nica" selected', $cuerpo);
    }

    public function testUnComprobanteSobreElCupoSeRechaza(): void
    {
        $this->postForm('registro', $this->cuenta());
        $usuario   = $this->rendidora(model(UsuarioModel::class)->porEmail('ana@acme.test'));
        $empresa   = model(EmpresaModel::class)->find($usuario['empresa_id']);
        $categoria = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();
        model(EmpresaModel::class)->update($empresa['id'], ['cupo_bytes' => 20]);
        $empresa   = model(EmpresaModel::class)->find($empresa['id']);
        $ruta      = WRITEPATH . 'nota-grande.pdf';
        file_put_contents($ruta, "%PDF-1.4\n" . str_repeat('a', 40));
        $archivo = new UploadedFile($ruta, 'grande.pdf', 'application/pdf', (int) filesize($ruta), UPLOAD_ERR_OK);

        $resultado = (new GastoServicio())->crear($usuario, $empresa, [
            'concepto'     => 'Taxi',
            'proveedor'    => 'Transporte Sur',
            'fecha'        => '2026-11-09',
            'monto'        => '12000',
            'categoria_id' => $categoria['id'],
            'tipo_doc'     => 'Boleta electrónica',
            'id_fiscal'    => '76.111.222-3',
            'num_doc'      => '101',
        ], $archivo);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('plan Free', implode(' ', $resultado['errores']));
        @unlink($ruta);
    }

    public function testElCentroDeLaEmpresaQuedaEnElGasto(): void
    {
        $this->postForm('registro', $this->cuenta());
        $usuario = $this->rendidora(model(UsuarioModel::class)->porEmail('ana@acme.test'));
        $empresa = model(EmpresaModel::class)->find($usuario['empresa_id']);
        $categoria = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();
        $lugares = new \App\Libraries\Lugares();
        $this->assertTrue($lugares->crear((int) $empresa['id'], 'centro', 'Comercial')['ok']);
        $centro = (int) $lugares->lista((int) $empresa['id'], 'centro')[0]['id'];
        $datos = [
            'concepto'     => 'Peaje',
            'proveedor'    => 'Ruta',
            'fecha'        => '2026-11-09',
            'monto'        => '4000',
            'categoria_id' => $categoria['id'],
            'tipo_doc'     => 'Boleta electrónica',
            'centro_id'    => (string) $centro,
        ];

        $apagado = (new GastoServicio())->crear($usuario, $empresa, $datos, null);
        $this->assertTrue($apagado['ok'], implode(' ', $apagado['errores']));
        $this->assertNull($apagado['gasto']['centro_id']);

        $pagina = $this->withSession(['usuario_id' => $usuario['id']])->get('parametros/lugares');
        $pagina->assertOK();
        $pagina->assertSee('planes de pago');
        $pagina->assertDontSee('Comercial');
        $pagina->assertDontSee('Habilitar para quien rinde');
        $indice = $this->withSession(['usuario_id' => $usuario['id']])->get('parametros');
        $indice->assertSee('En planes de pago.');
        $formulario = $this->withSession(['usuario_id' => $usuario['id']])->get('gastos/nuevo');
        $formulario->assertOK();
        $formulario->assertDontSee('Centro de costo');
        $formulario->assertDontSee('Sucursal');
        $formulario->assertDontSee('Otro concepto');

        model(EmpresaModel::class)->update($empresa['id'], ['plan' => 'cobro']);
        $empresa = model(EmpresaModel::class)->find($empresa['id']);
        $pagado = $this->withSession(['usuario_id' => $usuario['id']])->get('parametros/lugares');
        $pagado->assertSee('Habilitar para quien rinde');
        $pagado->assertDontSee('Comercial');
        $this->withSession(['usuario_id' => $usuario['id']])->get('gastos/nuevo')->assertDontSee('Centro de costo');

        $this->withSession(['usuario_id' => $usuario['id']]);
        $alta = $this->postForm('parametros/lugares/uso', ['tipo' => 'centro', 'habilitado' => '1']);
        $alta->assertRedirect();
        $prendido = $this->withSession(['usuario_id' => $usuario['id']])->get('parametros/lugares');
        $prendido->assertSee('Comercial');
        $prendido->assertSee('Deshabilitar para quien rinde');
        $empresa = model(EmpresaModel::class)->find($empresa['id']);

        $resultado = (new GastoServicio())->crear($usuario, $empresa, $datos, null);
        $this->assertTrue($resultado['ok'], implode(' ', $resultado['errores']));
        $this->assertSame($centro, (int) $resultado['gasto']['centro_id']);

        $ajeno = (new GastoServicio())->crear($usuario, $empresa, [
            'concepto'     => 'Otro',
            'proveedor'    => 'Ruta',
            'fecha'        => '2026-11-12',
            'monto'        => '1500',
            'categoria_id' => $categoria['id'],
            'tipo_doc'     => 'Boleta electrónica',
            'centro_id'    => '999',
        ], null);
        $this->assertFalse($ajeno['ok']);
        $this->assertStringContainsString('Elige un centro de la empresa.', implode(' ', $ajeno['errores']));

        $formulario = $this->withSession(['usuario_id' => $usuario['id']])->get('gastos/nuevo');
        $formulario->assertOK();
        $formulario->assertSee('Centro de costo');
        $formulario->assertSee('Comercial');
        $formulario->assertDontSee('Sucursal');
        $formulario->assertDontSee('Otro concepto');
        $formulario->assertSee('Debe completar toda la información solicitada.');
        $formulario->assertSee('name="monto" inputmode="decimal" required');

        model(EmpresaModel::class)->update($empresa['id'], ['usa_centro' => 0]);
        $empresa = model(EmpresaModel::class)->find($empresa['id']);
        $conserva = (new GastoServicio())->actualizar($usuario, $empresa, $resultado['gasto'], [
            'concepto'     => 'Peaje',
            'proveedor'    => 'Ruta',
            'fecha'        => '2026-11-09',
            'monto'        => '4000',
            'categoria_id' => $categoria['id'],
            'tipo_doc'     => 'Boleta electrónica',
            'centro_id'    => '999',
        ], null);
        $this->assertTrue($conserva['ok'], implode(' ', $conserva['errores']));
        $this->assertSame($centro, (int) $conserva['gasto']['centro_id']);
        $this->withSession(['usuario_id' => $usuario['id']])->get('gastos/nuevo')->assertDontSee('Centro de costo');
    }

    public function testLaTablaDeLegalizadosEsDeTodaLaEmpresaYSoloDelAdmin(): void
    {
        $this->postForm('registro', $this->cuenta());
        $ana       = $this->rendidora(model(UsuarioModel::class)->porEmail('ana@acme.test'));
        $empresa   = model(EmpresaModel::class)->find($ana['empresa_id']);
        $categoria = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();
        $alta      = (new \App\Libraries\PersonaServicio())->crear($empresa, [
            'nombre'   => 'Luis Soto',
            'email'    => 'luis@acme.test',
            'password' => 'clave-segura',
        ]);
        $this->assertTrue($alta['ok'], implode(' ', $alta['errores']));
        $luis = model(UsuarioModel::class)->find($alta['id']);
        $pdf  = $this->pdf();

        $deAna = (new GastoServicio())->crear($ana, $empresa, [
            'concepto'      => 'Almuerzo',
            'proveedor'     => 'Café Central',
            'fecha'         => '2026-11-09',
            'monto'         => '10000',
            'categoria_id'  => $categoria['id'],
            'tipo_doc'      => 'Factura electrónica',
            'id_fiscal'     => '76.111.222-3',
            'num_doc'       => '100',
            'codigo_fiscal' => '100',
        ], $pdf);
        $this->assertTrue($deAna['ok'], implode(' ', $deAna['errores']));
        $deLuis = (new GastoServicio())->crear($luis, $empresa, [
            'concepto'      => 'Taxi',
            'proveedor'     => 'Transporte Sur',
            'fecha'         => '2026-11-10',
            'monto'         => '8000',
            'categoria_id'  => $categoria['id'],
            'tipo_doc'      => 'Boleta electrónica',
            'id_fiscal'     => '77.222.333-4',
            'num_doc'       => '200',
            'codigo_fiscal' => '200',
        ], $this->pdf());
        $this->assertSame('legalizado', $deLuis['gasto']['estado']);
        (new GastoServicio())->crear($luis, $empresa, [
            'concepto'     => 'Sin papel',
            'proveedor'    => 'Kiosco',
            'fecha'        => '2026-11-11',
            'monto'        => '3000',
            'categoria_id' => $categoria['id'],
            'tipo_doc'     => 'Otro',
        ], null);

        $tabla = $this->withSession(['usuario_id' => $ana['id']])->get('gastos/legalizados');
        $tabla->assertOK();
        $tabla->assertSee('Documentos legalizados');
        $tabla->assertSee('Ana Pérez');
        $tabla->assertSee('Luis Soto');
        $tabla->assertSee('76.111.222-3');
        $tabla->assertSee('77.222.333-4');
        $tabla->assertSee('Café Central');
        $tabla->assertSee('Transporte Sur');
        $tabla->assertDontSee('Kiosco');
        $menu = $this->withSession(['usuario_id' => $ana['id']])->get('gastos');
        $menu->assertSee('Documentos legalizados');

        $ajeno = $this->withSession(['usuario_id' => $luis['id']])->get('gastos/legalizados');
        $ajeno->assertRedirectTo(site_url('gastos'));
        $ajeno->assertSessionHas('error', 'Esa tabla es del administrador.');
        $suyo = $this->withSession(['usuario_id' => $luis['id']])->get('gastos');
        $suyo->assertOK();
        $suyo->assertDontSee('Documentos legalizados');
        $suyo->assertDontSee('gastos/legalizados');
    }

    private function pdf(): UploadedFile
    {
        $ruta = WRITEPATH . 'legalizado-' . bin2hex(random_bytes(4)) . '.pdf';
        file_put_contents($ruta, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");

        return new UploadedFile($ruta, 'nota.pdf', 'application/pdf', (int) filesize($ruta), UPLOAD_ERR_OK);
    }

    /**
     * @param array<string, mixed> $usuario
     *
     * @return array<string, mixed>
     */
    private function rendidora(array $usuario): array
    {
        model(UsuarioModel::class)->update($usuario['id'], ['rinde' => 1]);

        return model(UsuarioModel::class)->find($usuario['id']);
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
            'acepta_politica' => '1',
        ];
    }
}
