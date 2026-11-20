<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Cache\CacheInterface;
use Config\AuthThrottle as AuthThrottleConfig;

/**
 * Throttle de autenticación vía caché de CI4 (file por defecto).
 * Cuenta por IP y por email; al superar el máximo aplica lockout temporal.
 */
class AuthThrottle
{
    private CacheInterface $cache;

    private AuthThrottleConfig $config;

    public function __construct(?CacheInterface $cache = null, ?AuthThrottleConfig $config = null)
    {
        $this->cache  = $cache ?? cache();
        $this->config = $config ?? config(AuthThrottleConfig::class);
    }

    public function loginBloqueado(string $ip, string $email): bool
    {
        return $this->estaBloqueado($this->clave('login', 'lock', 'ip', $ip))
            || ($email !== '' && $this->estaBloqueado($this->clave('login', 'lock', 'email', $email)));
    }

    public function registrarFalloLogin(string $ip, string $email): void
    {
        $this->registrarFallo(
            'login',
            'ip',
            $ip,
            $this->config->loginMaxAttempts,
            $this->config->loginWindowSeconds,
            $this->config->loginLockoutSeconds,
        );

        if ($email !== '') {
            $this->registrarFallo(
                'login',
                'email',
                $email,
                $this->config->loginMaxAttempts,
                $this->config->loginWindowSeconds,
                $this->config->loginLockoutSeconds,
            );
        }
    }

    public function limpiarLogin(string $ip, string $email): void
    {
        $this->cache->delete($this->clave('login', 'hits', 'ip', $ip));
        $this->cache->delete($this->clave('login', 'lock', 'ip', $ip));

        if ($email !== '') {
            $this->cache->delete($this->clave('login', 'hits', 'email', $email));
            $this->cache->delete($this->clave('login', 'lock', 'email', $email));
        }
    }

    public function registroBloqueado(string $ip, string $email): bool
    {
        return $this->estaBloqueado($this->clave('registro', 'lock', 'ip', $ip))
            || ($email !== '' && $this->estaBloqueado($this->clave('registro', 'lock', 'email', $email)));
    }

    /**
     * Cuenta un intento de registro (válido o no) y bloquea al superar el máximo.
     */
    public function registrarIntentoRegistro(string $ip, string $email): void
    {
        $this->registrarFallo(
            'registro',
            'ip',
            $ip,
            $this->config->registroMaxAttempts,
            $this->config->registroWindowSeconds,
            $this->config->registroLockoutSeconds,
        );

        if ($email !== '') {
            $this->registrarFallo(
                'registro',
                'email',
                $email,
                $this->config->registroMaxAttempts,
                $this->config->registroWindowSeconds,
                $this->config->registroLockoutSeconds,
            );
        }
    }

    public function mensajeBloqueo(): string
    {
        return 'Demasiados intentos. Espera unos minutos e inténtalo de nuevo.';
    }

    private function estaBloqueado(string $clave): bool
    {
        return $this->cache->get($clave) !== null;
    }

    private function registrarFallo(
        string $accion,
        string $dimension,
        string $valor,
        int $maxAttempts,
        int $windowSeconds,
        int $lockoutSeconds,
    ): void {
        $hitsKey = $this->clave($accion, 'hits', $dimension, $valor);
        $hits    = (int) ($this->cache->get($hitsKey) ?? 0) + 1;
        $this->cache->save($hitsKey, $hits, $windowSeconds);

        if ($hits >= $maxAttempts) {
            $this->cache->save(
                $this->clave($accion, 'lock', $dimension, $valor),
                1,
                $lockoutSeconds,
            );
        }
    }

    /**
     * Claves sin caracteres reservados del cache CI4 (`{}()/\@:`).
     */
    private function clave(string $accion, string $tipo, string $dimension, string $valor): string
    {
        return 'authth_' . $accion . '_' . $tipo . '_' . $dimension . '_' . hash('sha256', strtolower(trim($valor)));
    }
}
