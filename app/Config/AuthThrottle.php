<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Límites de intentos para login y registro (anti fuerza bruta / spam).
 */
class AuthThrottle extends BaseConfig
{
    /** Intentos fallidos de login antes del bloqueo (por IP y por email). */
    public int $loginMaxAttempts = 5;

    /** Ventana en segundos para contar intentos de login. */
    public int $loginWindowSeconds = 900;

    /** Duración del bloqueo de login en segundos. */
    public int $loginLockoutSeconds = 900;

    /** Intentos de registro (éxito o fallo) antes del bloqueo (por IP y por email). */
    public int $registroMaxAttempts = 5;

    /** Ventana en segundos para contar intentos de registro. */
    public int $registroWindowSeconds = 3600;

    /** Duración del bloqueo de registro en segundos. */
    public int $registroLockoutSeconds = 3600;
}
