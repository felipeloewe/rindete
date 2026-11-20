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
 * AuthZ / IDOR coverage for multi-tenant gastos and rol rendidor (M9).
 *
 * @internal
 */
final class AuthzTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testEmpresaNoVeGastoDeOtraEmpresa(): void
    {
        [$adminA] = $this->registrarEmpresa('Acme SpA', 'ana@acme.test', '12.345.678-5');
        [$adminB] = $this->registrarEmpresa('Beta Ltda', 'bob@beta.test', '76.543.210-3');

        $gastoA = $this->crearGasto($adminA, 'Gasto secreto Acme');

        $respuesta = $this->withSession(['usuario_id' => $adminB['id']])
            ->get('gastos/' . $gastoA['id']);

        $respuesta->assertRedirectTo(site_url('gastos'));
        $this->assertSame(1, model(GastoModel::class)->where('id', $gastoA['id'])->countAllResults());
    }

    public function testEmpresaNoDescargaComprobanteDeOtraEmpresa(): void
    {
        [$adminA] = $this->registrarEmpresa('Acme SpA', 'ana@acme.test', '12.345.678-5');
        [$adminB] = $this->registrarEmpresa('Beta Ltda', 'bob@beta.test', '76.543.210-3');

        $gastoA = $this->crearGasto($adminA, 'Taxi Acme', conComprobante: true);

        $respuesta = $this->withSession(['usuario_id' => $adminB['id']])
            ->get('gastos/' . $gastoA['id'] . '/comprobante');

        $respuesta->assertStatus(404);
    }

    public function testRendidorNoVeGastoDeOtroUsuario(): void
    {
        [$admin, $empresa] = $this->registrarEmpresa('Acme SpA', 'ana@acme.test', '12.345.678-5');
        $rendidor          = $this->crearRendidor($empresa, 'Luis Rendidor', 'luis@acme.test');

        $gastoAdmin = $this->crearGasto($admin, 'Almuerzo admin');

        $ver = $this->withSession(['usuario_id' => $rendidor['id']])
            ->get('gastos/' . $gastoAdmin['id']);
        $ver->assertRedirectTo(site_url('gastos'));

        $comprobante = $this->withSession(['usuario_id' => $rendidor['id']])
            ->get('gastos/' . $gastoAdmin['id'] . '/comprobante');
        $comprobante->assertStatus(404);
    }

    public function testRendidorSoloListaSusGastos(): void
    {
        [$admin, $empresa] = $this->registrarEmpresa('Acme SpA', 'ana@acme.test', '12.345.678-5');
        $rendidor          = $this->crearRendidor($empresa, 'Luis Rendidor', 'luis@acme.test');

        $this->crearGasto($admin, 'Concepto solo admin');
        $this->crearGasto($rendidor, 'Concepto del rendidor');

        $lista = $this->withSession(['usuario_id' => $rendidor['id']])->get('gastos');
        $lista->assertOK();
        $lista->assertSee('Concepto del rendidor');
        $lista->assertDontSee('Concepto solo admin');
    }

    public function testRendidorNoAccedeACategoriasNiEditaEmpresa(): void
    {
        [, $empresa] = $this->registrarEmpresa('Acme SpA', 'ana@acme.test', '12.345.678-5');
        $rendidor    = $this->crearRendidor($empresa, 'Luis Rendidor', 'luis@acme.test');

        $categorias = $this->withSession(['usuario_id' => $rendidor['id']])->get('categorias');
        $categorias->assertRedirectTo(site_url('panel'));

        $empresaPost = $this->withSession(['usuario_id' => $rendidor['id']])
            ->postForm('empresa', [
                'nombre'    => 'Hackeada SpA',
                'pais'      => 'CL',
                'id_fiscal' => '12.345.678-5',
            ]);
        $empresaPost->assertRedirectTo(site_url('empresa'));

        $empresaActual = model(EmpresaModel::class)->find($empresa['id']);
        $this->assertSame('Acme SpA', $empresaActual['nombre']);
    }

    public function testRendidorPuedeVerSuPropioComprobante(): void
    {
        [, $empresa] = $this->registrarEmpresa('Acme SpA', 'ana@acme.test', '12.345.678-5');
        $rendidor    = $this->crearRendidor($empresa, 'Luis Rendidor', 'luis@acme.test');
        $gasto       = $this->crearGasto($rendidor, 'Taxi propio', conComprobante: true);

        $respuesta = $this->withSession(['usuario_id' => $rendidor['id']])
            ->get('gastos/' . $gasto['id'] . '/comprobante');

        $respuesta->assertOK();
        $respuesta->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function registrarEmpresa(string $nombre, string $email, string $idFiscal): array
    {
        // Evitar que una sesión previa (otro registro en el mismo test) interfiera.
        $this->withSession([])->get('logout');

        $resultado = $this->postForm('registro', [
            'pais'      => 'CL',
            'empresa'   => $nombre,
            'id_fiscal' => $idFiscal,
            'nombre'    => 'Admin ' . $nombre,
            'email'     => $email,
            'password'  => 'clave-segura',
        ]);
        $resultado->assertRedirectTo(site_url('panel'));

        $usuario = model(UsuarioModel::class)->where('email', $email)->first();
        $this->assertIsArray($usuario);
        $empresa = model(EmpresaModel::class)->find($usuario['empresa_id']);
        $this->assertIsArray($empresa);

        return [$usuario, $empresa];
    }

    /**
     * @param array<string, mixed> $empresa
     *
     * @return array<string, mixed>
     */
    private function crearRendidor(array $empresa, string $nombre, string $email): array
    {
        $id = model(UsuarioModel::class)->insert([
            'empresa_id' => $empresa['id'],
            'nombre'     => $nombre,
            'email'      => $email,
            'password'   => password_hash('clave-segura', PASSWORD_DEFAULT),
            'rol'        => 'rendidor',
            'activo'     => 1,
            'creado'     => date('Y-m-d H:i:s'),
        ], true);

        $usuario = model(UsuarioModel::class)->find($id);
        $this->assertIsArray($usuario);

        return $usuario;
    }

    /**
     * @param array<string, mixed> $usuario
     *
     * @return array<string, mixed>
     */
    private function crearGasto(array $usuario, string $concepto, bool $conComprobante = false): array
    {
        $empresa   = model(EmpresaModel::class)->find($usuario['empresa_id']);
        $this->assertIsArray($empresa);
        $categoria = model(CategoriaModel::class)
            ->where('empresa_id', $empresa['id'])
            ->where('nombre', 'Alimentación')
            ->first();
        $this->assertIsArray($categoria);

        $archivo = null;
        $ruta    = null;
        if ($conComprobante) {
            $ruta = WRITEPATH . 'authz-comprobante.pdf';
            file_put_contents($ruta, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");
            $archivo = new UploadedFile($ruta, 'nota.pdf', 'application/pdf', (int) filesize($ruta), UPLOAD_ERR_OK);
        }

        $resultado = (new GastoServicio())->crear($usuario, $empresa, [
            'concepto'     => $concepto,
            'proveedor'    => 'Proveedor Test',
            'fecha'        => '2026-11-09',
            'monto'        => '12000',
            'categoria_id' => $categoria['id'],
            'tipo_doc'     => 'Boleta',
            'id_fiscal'    => '76.111.222-3',
            'num_doc'      => (string) random_int(1, 99999),
        ], $archivo);

        if ($ruta !== null) {
            @unlink($ruta);
        }

        $this->assertTrue($resultado['ok'], implode(' ', $resultado['errores'] ?? []));
        $this->assertIsArray($resultado['gasto']);

        return $resultado['gasto'];
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
