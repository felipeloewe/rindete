<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\Email;

/**
 * Manda la clave inicial al administrador. Si el correo no está configurado, no finge el envío.
 */
class CorreoAcceso
{
    public function enviar(string $destino, string $nombre, string $empresa, string $clave): bool
    {
        $config = config(Email::class);
        if ($config->fromEmail === '' || $destino === '') {
            return false;
        }
        $texto = "Hola {$nombre}.\n\n"
            . "Quedó creado tu acceso de administrador de {$empresa} en Rindete.\n\n"
            . 'Entras en ' . site_url('login') . "\n"
            . "Correo: {$destino}\n"
            . "Clave inicial: {$clave}\n\n"
            . "Al entrar la cambias. Después das de alta a las personas que caben en el plan.\n";
        try {
            $mail = service('email');
            $mail->setFrom($config->fromEmail, $config->fromName !== '' ? $config->fromName : 'Rindete');
            $mail->setTo($destino);
            $mail->setSubject('Tu acceso de administrador en Rindete');
            $mail->setMessage($texto);

            return $mail->send(false);
        } catch (\Throwable) {
            return false;
        }
    }
}
