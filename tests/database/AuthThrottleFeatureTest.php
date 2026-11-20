<?php

declare(strict_types=1);

use App\Models\UsuarioModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\AuthThrottle as AuthThrottleConfig;

/**
 * @internal
 */
final class AuthThrottleFeatureTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->clean();

        $config                      = config(AuthThrottleConfig::class);
        $config->loginMaxAttempts    = 3;
        $config->loginWindowSeconds  = 60;
        $config->loginLockoutSeconds = 60;
        $config->registroMaxAttempts = 3;
        $config->registroWindowSeconds = 60;
        $config->registroLockoutSeconds = 60;
    }

    public function testLoginBloqueaTrasFallosRepetidos(): void
    {
        $this->postForm('registro', $this->datos());
        $this->postForm('logout', []);
        cache()->clean();

        for ($i = 0; $i < 3; $i++) {
            $fallo = $this->postForm('login', [
                'email'    => 'ana@acme.test',
                'password' => 'clave-incorrecta',
            ]);
            $fallo->assertRedirect();
        }

        $bloqueado = $this->postForm('login', [
            'email'    => 'ana@acme.test',
            'password' => 'clave-segura',
        ]);
        $bloqueado->assertRedirect();
        $bloqueado->assertSessionHas(
            'error',
            'Demasiados intentos. Espera unos minutos e inténtalo de nuevo.',
        );
    }

    public function testLoginExitosoLimpiaElThrottle(): void
    {
        $this->postForm('registro', $this->datos());
        $this->postForm('logout', []);
        cache()->clean();

        $this->postForm('login', [
            'email'    => 'ana@acme.test',
            'password' => 'clave-incorrecta',
        ]);
        $this->postForm('login', [
            'email'    => 'ana@acme.test',
            'password' => 'clave-incorrecta',
        ]);

        $ok = $this->postForm('login', [
            'email'    => 'ana@acme.test',
            'password' => 'clave-segura',
        ]);
        $ok->assertRedirectTo(site_url('panel'));

        $this->postForm('logout', []);

        $this->postForm('login', [
            'email'    => 'ana@acme.test',
            'password' => 'clave-incorrecta',
        ]);
        $this->postForm('login', [
            'email'    => 'ana@acme.test',
            'password' => 'clave-incorrecta',
        ]);

        $sigue = $this->postForm('login', [
            'email'    => 'ana@acme.test',
            'password' => 'clave-segura',
        ]);
        $sigue->assertRedirectTo(site_url('panel'));
    }

    public function testRegistroBloqueaTrasIntentosRepetidos(): void
    {
        $cuentas = [
            ['empresa' => 'Empresa 1', 'id_fiscal' => '11.111.111-1', 'email' => 'user1@acme.test'],
            ['empresa' => 'Empresa 2', 'id_fiscal' => '22.222.222-2', 'email' => 'user2@acme.test'],
            ['empresa' => 'Empresa 3', 'id_fiscal' => '55.555.555-5', 'email' => 'user3@acme.test'],
        ];

        foreach ($cuentas as $cuenta) {
            $alta = $this->postForm('registro', $this->datos($cuenta));
            $alta->assertRedirectTo(site_url('panel'));
            $this->postForm('logout', []);
        }

        $bloqueado = $this->postForm('registro', $this->datos([
            'empresa'   => 'Otra',
            'id_fiscal' => '98.765.432-5',
            'email'     => 'final@acme.test',
        ]));
        $bloqueado->assertRedirect();
        $bloqueado->assertSessionHas(
            'errores',
            ['Demasiados intentos. Espera unos minutos e inténtalo de nuevo.'],
        );
        $this->assertNull(model(UsuarioModel::class)->where('email', 'final@acme.test')->first());
    }

    public function testLogoutSoloPorPost(): void
    {
        $this->postForm('registro', $this->datos());
        $usuario = model(UsuarioModel::class)->where('email', 'ana@acme.test')->first();
        $this->assertIsArray($usuario);

        try {
            $this->withSession(['usuario_id' => $usuario['id']])->get('logout');
            $this->fail('GET /logout debería fallar sin ruta.');
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
            $this->assertStringContainsString('logout', $e->getMessage());
        }

        $panel = $this->withSession(['usuario_id' => $usuario['id']])->get('panel');
        $panel->assertOK();

        $post = $this->withSession(['usuario_id' => $usuario['id']])->postForm('logout', []);
        $post->assertRedirectTo(site_url('login'));
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
