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
final class RegistroTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $namespace   = 'App';
    protected $refresh     = true;

    public function testRegistroCreaEmpresaYAdministrador(): void
    {
        $resultado = $this->postForm('registro', $this->datos());

        $resultado->assertRedirectTo(site_url('panel'));
        $this->assertSame(1, model(EmpresaModel::class)->where('id_fiscal', '12345678-5')->countAllResults());

        $usuario = model(UsuarioModel::class)->where('email', 'ana@acme.test')->first();
        $this->assertIsArray($usuario);
        $this->assertSame('administrador', $usuario['rol']);
        $this->assertTrue(password_verify('clave-segura', $usuario['password']));
    }

    public function testNoRepiteCorreoNiIdentificacion(): void
    {
        $this->postForm('registro', $this->datos());
        $otro = $this->postForm('registro', $this->datos([
            'empresa' => 'Otra',
            'email'   => 'otro@acme.test',
        ]));

        $otro->assertRedirect();
        $this->assertSame(1, model(EmpresaModel::class)->countAllResults());
    }

    public function testLoginEntraAlPanel(): void
    {
        $this->postForm('registro', $this->datos());
        $this->get('logout');

        $login = $this->postForm('login', [
            'email'    => 'ana@acme.test',
            'password' => 'clave-segura',
        ]);

        $login->assertRedirectTo(site_url('panel'));
        $usuario = model(UsuarioModel::class)->where('email', 'ana@acme.test')->first();
        $panel = $this->withSession(['usuario_id' => $usuario['id']])->get('panel');
        $panel->assertOK();
        $panel->assertSee('Acme SpA');
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
     * @param array<string, string> $cambios
     *
     * @return array<string, string>
     */
    private function datos(array $cambios = []): array
    {
        return array_merge([
            'pais'      => 'CL',
            'empresa'   => 'Acme SpA',
            'id_fiscal' => '12.345.678-5',
            'nombre'    => 'Ana Pérez',
            'email'     => 'ana@acme.test',
            'password'  => 'clave-segura',
        ], $cambios);
    }
}
