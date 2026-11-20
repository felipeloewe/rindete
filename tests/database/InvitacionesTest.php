<?php

declare(strict_types=1);

use App\Libraries\Invitacion;
use App\Models\EmpresaModel;
use App\Models\UsuarioModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * M4 — invitación / activación / desactivación de rendidores + aislamiento.
 *
 * @internal
 */
final class InvitacionesTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testFlujoInvitarAceptarYLogin(): void
    {
        $admin   = $this->registrar('Acme SpA', 'ana@acme.test', '12.345.678-5');
        $empresa = model(EmpresaModel::class)->find($admin['empresa_id']);

        $http = $this->withSession(['usuario_id' => $admin['id']])->postForm('usuarios/invitar', [
            'nombre' => 'Luis Rendidor',
            'email'  => 'luis@acme.test',
        ]);
        $http->assertRedirectTo(site_url('usuarios'));

        $pendiente = model(UsuarioModel::class)->where('email', 'luis@acme.test')->first();
        $this->assertIsArray($pendiente);
        $this->assertSame('rendidor', $pendiente['rol']);
        $this->assertSame(0, (int) $pendiente['activo']);
        $this->assertNotEmpty($pendiente['invite_token']);

        // Nuevo enlace (re-invitación) para conocer el token en claro en el test.
        $inv = (new Invitacion())->invitar($admin, $empresa, 'Luis Rendidor', 'luis@acme.test');
        $this->assertTrue($inv['ok']);
        $token = $this->tokenDesdeEnlace($inv['enlace']);

        $this->withSession([])->get('logout');
        $aceptar = $this->postForm('invitar/' . $token, ['password' => 'clave-rendidor']);
        $aceptar->assertRedirectTo(site_url('panel'));

        $activo = model(UsuarioModel::class)->where('email', 'luis@acme.test')->first();
        $this->assertSame(1, (int) $activo['activo']);
        $this->assertTrue($activo['invite_token'] === null || $activo['invite_token'] === '');
        $this->assertTrue(password_verify('clave-rendidor', $activo['password']));
    }

    public function testRendidorNoGestionaPersonas(): void
    {
        [$admin, $empresa] = $this->empresaConRendidorActivo();

        $lista = $this->withSession(['usuario_id' => $admin['rendidor_id']])->get('usuarios');
        $lista->assertRedirectTo(site_url('panel'));

        $intento = $this->withSession(['usuario_id' => $admin['rendidor_id']])->postForm('usuarios/invitar', [
            'nombre' => 'Otro',
            'email'  => 'otro@acme.test',
        ]);
        $intento->assertRedirectTo(site_url('panel'));
        $this->assertSame(0, model(UsuarioModel::class)->where('email', 'otro@acme.test')->countAllResults());
        unset($empresa);
    }

    public function testDesactivarImpideLoginYNoCruzaEmpresas(): void
    {
        [$adminA, $empresaA, $rendidor] = $this->empresaConRendidorActivo();
        $adminB = $this->registrar('Beta Ltda', 'bob@beta.test', '76.543.210-3');

        $this->withSession(['usuario_id' => $adminA['id']])
            ->postForm('usuarios/' . $rendidor['id'] . '/desactivar', [])
            ->assertRedirectTo(site_url('usuarios'));
        $this->assertSame(0, (int) model(UsuarioModel::class)->find($rendidor['id'])['activo']);

        $this->withSession([])->get('logout');
        $login = $this->postForm('login', [
            'email'    => 'luis@acme.test',
            'password' => 'clave-rendidor',
        ]);
        $this->assertStringNotContainsString('panel', (string) $login->getRedirectUrl());

        $antes = (int) model(UsuarioModel::class)->find($rendidor['id'])['activo'];
        $this->withSession(['usuario_id' => $adminB['id']])
            ->postForm('usuarios/' . $rendidor['id'] . '/desactivar', [])
            ->assertRedirectTo(site_url('usuarios'));
        $this->assertSame($antes, (int) model(UsuarioModel::class)->find($rendidor['id'])['activo']);
        $this->assertSame((int) $empresaA['id'], (int) $rendidor['empresa_id']);
        $this->assertNotSame((int) $adminB['empresa_id'], (int) $rendidor['empresa_id']);
    }

    public function testActivarTrasDesactivar(): void
    {
        [$admin, $empresa, $rendidor] = $this->empresaConRendidorActivo();
        (new Invitacion())->desactivar($admin, $empresa, (int) $rendidor['id']);
        $ok = (new Invitacion())->activar($admin, $empresa, (int) $rendidor['id']);

        $this->assertTrue($ok['ok']);
        $this->assertSame(1, (int) model(UsuarioModel::class)->find($rendidor['id'])['activo']);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>}
     */
    private function empresaConRendidorActivo(): array
    {
        $admin   = $this->registrar('Acme SpA', 'ana@acme.test', '12.345.678-5');
        $empresa = model(EmpresaModel::class)->find($admin['empresa_id']);
        $inv     = (new Invitacion())->invitar($admin, $empresa, 'Luis Rendidor', 'luis@acme.test');
        $this->assertTrue($inv['ok']);
        (new Invitacion())->aceptar($this->tokenDesdeEnlace($inv['enlace']), 'clave-rendidor');
        $rendidor = model(UsuarioModel::class)->where('email', 'luis@acme.test')->first();
        $this->assertIsArray($rendidor);
        $admin['rendidor_id'] = $rendidor['id'];

        return [$admin, $empresa, $rendidor];
    }

    /**
     * @return array<string, mixed>
     */
    private function registrar(string $empresa, string $email, string $idFiscal): array
    {
        $this->withSession([])->get('logout');
        $this->postForm('registro', [
            'pais'      => 'CL',
            'empresa'   => $empresa,
            'id_fiscal' => $idFiscal,
            'nombre'    => 'Admin',
            'email'     => $email,
            'password'  => 'clave-segura',
        ])->assertRedirectTo(site_url('panel'));

        $usuario = model(UsuarioModel::class)->where('email', $email)->first();
        $this->assertIsArray($usuario);

        return $usuario;
    }

    private function tokenDesdeEnlace(string $enlace): string
    {
        $partes = explode('/', rtrim($enlace, '/'));

        return (string) end($partes);
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
