<?php

declare(strict_types=1);

use App\Libraries\AuthThrottle;
use CodeIgniter\Test\CIUnitTestCase;
use Config\AuthThrottle as AuthThrottleConfig;

/**
 * @internal
 */
final class AuthThrottleTest extends CIUnitTestCase
{
    private AuthThrottle $throttle;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->clean();

        $config                       = new AuthThrottleConfig();
        $config->loginMaxAttempts     = 3;
        $config->loginWindowSeconds   = 60;
        $config->loginLockoutSeconds  = 60;
        $config->registroMaxAttempts  = 3;
        $config->registroWindowSeconds = 60;
        $config->registroLockoutSeconds = 60;

        $this->throttle = new AuthThrottle(cache(), $config);
    }

    public function testLoginSeBloqueaTrasMaximosFallosPorEmail(): void
    {
        $ip    = '203.0.113.10';
        $email = 'victima@example.test';

        $this->assertFalse($this->throttle->loginBloqueado($ip, $email));

        $this->throttle->registrarFalloLogin($ip, $email);
        $this->throttle->registrarFalloLogin($ip, $email);
        $this->assertFalse($this->throttle->loginBloqueado($ip, $email));

        $this->throttle->registrarFalloLogin($ip, $email);
        $this->assertTrue($this->throttle->loginBloqueado($ip, $email));
        $this->assertTrue($this->throttle->loginBloqueado('198.51.100.1', $email));
    }

    public function testLoginSeBloqueaPorIpIndependienteDelEmail(): void
    {
        $ip = '203.0.113.20';

        $this->throttle->registrarFalloLogin($ip, 'a@example.test');
        $this->throttle->registrarFalloLogin($ip, 'b@example.test');
        $this->throttle->registrarFalloLogin($ip, 'c@example.test');

        $this->assertTrue($this->throttle->loginBloqueado($ip, 'otro@example.test'));
    }

    public function testLimpiarLoginQuitaElBloqueo(): void
    {
        $ip    = '203.0.113.30';
        $email = 'ok@example.test';

        $this->throttle->registrarFalloLogin($ip, $email);
        $this->throttle->registrarFalloLogin($ip, $email);
        $this->throttle->registrarFalloLogin($ip, $email);
        $this->assertTrue($this->throttle->loginBloqueado($ip, $email));

        $this->throttle->limpiarLogin($ip, $email);
        $this->assertFalse($this->throttle->loginBloqueado($ip, $email));
    }

    public function testRegistroSeBloqueaTrasMaximosIntentos(): void
    {
        $ip    = '203.0.113.40';
        $email = 'nueva@example.test';

        $this->throttle->registrarIntentoRegistro($ip, $email);
        $this->throttle->registrarIntentoRegistro($ip, $email);
        $this->assertFalse($this->throttle->registroBloqueado($ip, $email));

        $this->throttle->registrarIntentoRegistro($ip, $email);
        $this->assertTrue($this->throttle->registroBloqueado($ip, $email));
    }
}
