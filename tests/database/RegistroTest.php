<?php

declare(strict_types=1);

use App\Models\CategoriaModel;
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
        $empresa = model(EmpresaModel::class)->porFiscal('CL', '12345678-5');
        $this->assertIsArray($empresa);
        $this->assertSame('free', $empresa['plan']);
        $this->assertSame(4, (int) $empresa['cupo_usuarios']);
        $this->assertSame(2 * 1024 * 1024 * 1024, (int) $empresa['cupo_bytes']);

        $usuario = model(UsuarioModel::class)->porEmail('ana@acme.test');
        $this->assertIsArray($usuario);
        $this->assertSame('administrador', $usuario['rol']);
        $this->assertSame(\App\Libraries\PoliticaDatos::VERSION, $usuario['politica_version']);
        $this->assertSame(\App\Libraries\PoliticaDatos::VERSION, $empresa['politica_version']);
        $this->assertSame(6, model(CategoriaModel::class)->where('empresa_id', $usuario['empresa_id'])->countAllResults());
        $this->assertTrue(password_verify('clave-segura', $usuario['password']));
    }

    public function testSinAceptarLaPoliticaNoCreaLaCuenta(): void
    {
        $resultado = $this->postForm('registro', $this->datos(['acepta_politica' => '']));
        $resultado->assertRedirect();
        $this->assertSame(0, model(EmpresaModel::class)->countAllResults());
    }

    public function testCondicionesDeUsoExplicanElEfectoDeCadaLey(): void
    {
        $condiciones = $this->get('condiciones');
        $condiciones->assertOK();
        $condiciones->assertSee('Condiciones de uso del software');
        $condiciones->assertSee('Ley N° 19.628');
        $condiciones->assertSee('Ley N° 21.719');
        $condiciones->assertSee('4 usuarios y 2 GB');

        $registro = $this->get('registro');
        $registro->assertOK();
        $registro->assertSee('Infórmate más');
        $registro->assertSee('Acepto las condiciones de uso y los efectos que se indican para cada ley.');
        $registro->assertSee('Debe completar toda la información solicitada.');
        $registro->assertSee('name="acepta_politica" value="1" required');
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
            'acepta_politica' => '1',
        ]);

        $login->assertRedirectTo(site_url('panel'));
        $usuario = model(UsuarioModel::class)->porEmail('ana@acme.test');
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
            'acepta_politica' => '1',
        ], $cambios);
    }
}
