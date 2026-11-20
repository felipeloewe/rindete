<?php

declare(strict_types=1);

use App\Libraries\GastoServicio;
use App\Libraries\PreAsiento;
use App\Models\CategoriaModel;
use App\Models\CuentaModel;
use App\Models\EmpresaModel;
use App\Models\GastoModel;
use App\Models\TipoDocumentoModel;
use App\Models\UsuarioModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class ParametrosTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testElAltaDejaLosTiposYLosGastosAceptados(): void
    {
        $this->entrar();
        $empresa = model(EmpresaModel::class)->first();
        $tipos   = model(TipoDocumentoModel::class)->where('empresa_id', $empresa['id'])->findAll();
        $nombres = array_column($tipos, 'nombre');

        $this->assertContains('Boleta', $nombres);
        $this->assertSame(1, (int) $empresa['parametros_sembrados']);
        $this->assertSame(6, db_connect()->table('gastos_aceptados')->countAllResults());
    }

    public function testLaCargaObservaElTopeDelTipoYLaCategoria(): void
    {
        $cuenta = $this->entrar();
        $boleta = model(TipoDocumentoModel::class)->where('nombre', 'Boleta')->first();
        model(TipoDocumentoModel::class)->update($boleta['id'], ['monto_aceptado' => 5000]);
        $usuario   = model(UsuarioModel::class)->find($cuenta['usuario_id']);
        $empresa   = model(EmpresaModel::class)->find($usuario['empresa_id']);
        $categoria = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();

        $sobre = (new GastoServicio())->crear($usuario, $empresa, $this->gasto($categoria['id'], '20000'), null);
        $this->assertSame('observado', $sobre['gasto']['estado']);
        $this->assertSame(1, (int) $sobre['gasto']['fuera_tipo']);

        model(TipoDocumentoModel::class)->update($boleta['id'], ['monto_aceptado' => 0, 'sin_comprobante' => 1]);
        $libre = (new GastoServicio())->crear($usuario, $empresa, $this->gasto($categoria['id'], '8000', '102'), null);
        $this->assertSame('legalizado', $libre['gasto']['estado']);
        $this->assertSame(0, (int) $libre['gasto']['sin_comprobante']);

        db_connect()->table('gastos_aceptados')->where('categoria_id', $categoria['id'])->delete();
        $ajeno = (new GastoServicio())->crear($usuario, $empresa, $this->gasto($categoria['id'], '3000', '103'), $this->pdf());
        $this->assertSame('observado', $ajeno['gasto']['estado']);
        $this->assertSame(1, (int) $ajeno['gasto']['no_aceptado']);
    }

    public function testUnGastoLegalizadoProponeElAsiento(): void
    {
        $cuenta    = $this->entrar();
        $usuario   = model(UsuarioModel::class)->find($cuenta['usuario_id']);
        $empresa   = model(EmpresaModel::class)->find($usuario['empresa_id']);
        $categoria = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();
        $cuentaId  = model(CuentaModel::class)->insert([
            'empresa_id' => $empresa['id'],
            'clase'      => 'generica',
            'codigo'     => '5101',
            'nombre'     => 'Alimentación',
            'concepto'   => '',
            'usuario_id' => null,
            'activa'     => 1,
        ], true);
        db_connect()->table('categoria_cuentas')->insert([
            'categoria_id' => $categoria['id'],
            'cuenta_id'    => $cuentaId,
        ]);

        $resultado = (new GastoServicio())->crear($usuario, $empresa, $this->gasto($categoria['id'], '12000', '104'), $this->pdf());
        $propuesto = PreAsiento::deGasto((int) $resultado['gasto']['id']);

        $this->assertSame('legalizado', $resultado['gasto']['estado']);
        $this->assertIsArray($propuesto);
        $this->assertSame('propuesto', $propuesto['estado']);
        $this->assertSame('5101', $propuesto['codigo']);
        $this->assertSame(12000.0, (float) $propuesto['monto']);
    }

    public function testElAdministradorVeLaParametrizacion(): void
    {
        $cuenta = $this->entrar();
        $pagina = $this->withSession(['usuario_id' => $cuenta['usuario_id']])->get('parametros');
        $pagina->assertOK();
        $pagina->assertSee('Parametrización');
        $pagina->assertSee('Cuentas');
        $pagina->assertSee('Clasificación');
        $pagina->assertSee('Conexiones');
        $pagina->assertSee('Tipos de documento');
        $pagina->assertDontSee('Guardar cuenta');

        $tipos = $this->withSession(['usuario_id' => $cuenta['usuario_id']])->get('parametros/tipos');
        $tipos->assertOK();
        $tipos->assertSee('Boleta');

        $rendidor = model(UsuarioModel::class)->insert([
            'empresa_id' => model(UsuarioModel::class)->find($cuenta['usuario_id'])['empresa_id'],
            'nombre'     => 'Luis',
            'email'      => 'luis@acme.test',
            'password'   => password_hash('clave-segura', PASSWORD_DEFAULT),
            'rol'        => 'rendidor',
            'activo'     => 1,
            'creado'     => date('Y-m-d H:i:s'),
        ], true);
        $vista = $this->withSession(['usuario_id' => $rendidor])->get('parametros');
        $vista->assertRedirectTo(site_url('panel'));
    }

    /**
     * @return array{usuario_id: int}
     */
    private function entrar(): array
    {
        $this->postForm('registro', [
            'pais'      => 'CL',
            'empresa'   => 'Acme SpA',
            'id_fiscal' => '12.345.678-5',
            'nombre'    => 'Ana Pérez',
            'email'     => 'ana@acme.test',
            'password'  => 'clave-segura',
        ]);
        $usuario = model(UsuarioModel::class)->porEmail('ana@acme.test');

        return ['usuario_id' => (int) $usuario['id']];
    }

    /**
     * @return array<string, string>
     */
    private function gasto(int|string $categoriaId, string $monto, string $numero = '101'): array
    {
        return [
            'concepto'     => 'Almuerzo',
            'proveedor'    => 'Café Central',
            'fecha'        => '2026-11-09',
            'monto'        => $monto,
            'categoria_id' => (string) $categoriaId,
            'tipo_doc'     => 'Boleta',
            'id_fiscal'    => '76.111.222-3',
            'num_doc'      => $numero,
        ];
    }

    private function pdf(): UploadedFile
    {
        $ruta = WRITEPATH . 'parametros-prueba.pdf';
        file_put_contents($ruta, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");

        return new UploadedFile($ruta, 'nota.pdf', 'application/pdf', (int) filesize($ruta), UPLOAD_ERR_OK);
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
