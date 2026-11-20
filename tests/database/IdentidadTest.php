<?php

declare(strict_types=1);

use App\Models\EmpresaModel;
use App\Models\UsuarioModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class IdentidadTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testLaOperacionGuardaElIdYElLoginSigue(): void
    {
        $this->postForm('registro', [
            'pais'      => 'CL',
            'empresa'   => 'Acme SpA',
            'id_fiscal' => '12.345.678-5',
            'nombre'    => 'Ana Pérez',
            'email'     => 'ana@acme.test',
            'password'  => 'clave-segura',
            'acepta_politica' => '1',
        ]);

        $operacion = db_connect()->table('usuarios')->get()->getRowArray();
        $this->assertIsArray($operacion);
        $this->assertArrayNotHasKey('email', $operacion);
        $this->assertArrayNotHasKey('nombre', $operacion);
        $this->assertArrayNotHasKey('password', $operacion);
        $this->assertArrayNotHasKey('id_fiscal', $operacion);
        $this->assertArrayNotHasKey('cuenta_banco', $operacion);

        $empresa = db_connect()->table('empresas')->where('id', $operacion['empresa_id'])->get()->getRowArray();
        $this->assertIsArray($empresa);
        $this->assertArrayNotHasKey('nombre', $empresa);
        $this->assertArrayNotHasKey('id_fiscal', $empresa);
        $this->assertArrayNotHasKey('cuenta_cargo', $empresa);
        $this->assertSame('CL', $empresa['pais']);

        $persona = db_connect('identidad')->table('personas')->where('usuario_id', $operacion['id'])->get()->getRowArray();
        $this->assertIsArray($persona);
        $this->assertSame('ana@acme.test', $persona['email']);
        $this->assertSame('Ana Pérez', $persona['nombre']);
        $this->assertTrue(password_verify('clave-segura', (string) $persona['password']));

        $organizacion = db_connect('identidad')->table('organizaciones')->where('empresa_id', $operacion['empresa_id'])->get()->getRowArray();
        $this->assertIsArray($organizacion);
        $this->assertSame('12345678-5', $organizacion['id_fiscal']);
        $this->assertSame('Acme SpA', $organizacion['nombre']);

        $this->get('logout');
        $login = $this->postForm('login', [
            'email'    => 'ana@acme.test',
            'password' => 'clave-segura',
            'acepta_politica' => '1',
        ]);
        $login->assertRedirectTo(site_url('panel'));

        $vista = model(UsuarioModel::class)->find($operacion['id']);
        $this->assertSame('Ana Pérez', $vista['nombre']);
        $empresaVista = model(EmpresaModel::class)->find($operacion['empresa_id']);
        $this->assertSame('Acme SpA', $empresaVista['nombre']);
        $this->assertSame('12345678-5', $empresaVista['id_fiscal']);
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
