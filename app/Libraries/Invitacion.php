<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\UsuarioModel;
use Config\Email as EmailConfig;

class Invitacion
{
    public const DIAS_VALIDEZ = 7;

    /**
     * @param array<string, mixed> $admin
     * @param array<string, mixed> $empresa
     *
     * @return array{ok: bool, errores: list<string>, usuario?: array<string, mixed>, enlace?: string, enviado?: bool}
     */
    public function invitar(array $admin, array $empresa, string $nombre, string $email): array
    {
        if (($admin['rol'] ?? '') !== 'administrador' || (int) $admin['empresa_id'] !== (int) $empresa['id']) {
            return ['ok' => false, 'errores' => ['Solo el administrador puede invitar rendidores.']];
        }

        $nombre = trim($nombre);
        $email  = strtolower(trim($email));
        $errores = [];
        if ($nombre === '' || strlen($nombre) > 80) {
            $errores[] = 'Escribe el nombre de la persona.';
        }
        if (! IdFiscal::emailValido($email)) {
            $errores[] = 'El correo no es válido.';
        }
        if ($errores !== []) {
            return ['ok' => false, 'errores' => $errores];
        }

        $usuarios = model(UsuarioModel::class);
        $existente = $usuarios->where('email', $email)->first();
        if (is_array($existente) && (int) $existente['empresa_id'] !== (int) $empresa['id']) {
            return ['ok' => false, 'errores' => ['Ese correo ya tiene una cuenta en otra empresa.']];
        }
        if (is_array($existente) && (string) $existente['rol'] === 'administrador') {
            return ['ok' => false, 'errores' => ['No se puede invitar de nuevo a un administrador.']];
        }

        [$token, $hash, $expira] = $this->nuevoToken();
        $ahora = date('Y-m-d H:i:s');

        if (is_array($existente)) {
            $usuarios->update($existente['id'], [
                'nombre'         => $nombre,
                'rol'            => 'rendidor',
                'activo'         => 0,
                'invite_token'   => $hash,
                'invite_expires' => $expira,
            ]);
            $usuarioId = (int) $existente['id'];
        } else {
            $usuarioId = (int) $usuarios->insert([
                'empresa_id'     => (int) $empresa['id'],
                'nombre'         => $nombre,
                'email'          => $email,
                'password'       => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                'rol'            => 'rendidor',
                'activo'         => 0,
                'invite_token'   => $hash,
                'invite_expires' => $expira,
                'creado'         => $ahora,
            ], true);
        }

        $usuario = $usuarios->find($usuarioId);
        if (! is_array($usuario)) {
            return ['ok' => false, 'errores' => ['No pudimos crear la invitación.']];
        }

        $enlace  = site_url('invitar/' . $token);
        $enviado = $this->enviarCorreo($email, $nombre, (string) $empresa['nombre'], $enlace);

        return [
            'ok'      => true,
            'errores' => [],
            'usuario' => $usuario,
            'enlace'  => $enlace,
            'enviado' => $enviado,
        ];
    }

    /**
     * @return array{ok: bool, errores: list<string>, usuario?: array<string, mixed>}
     */
    public function aceptar(string $token, string $password): array
    {
        $usuario = $this->porToken($token);
        if ($usuario === null) {
            return ['ok' => false, 'errores' => ['La invitación no es válida o ya expiró.']];
        }
        if (strlen($password) < 8) {
            return ['ok' => false, 'errores' => ['La contraseña debe tener al menos 8 caracteres.']];
        }

        model(UsuarioModel::class)->update($usuario['id'], [
            'password'       => password_hash($password, PASSWORD_DEFAULT),
            'activo'         => 1,
            'invite_token'   => null,
            'invite_expires' => null,
        ]);

        $actualizado = model(UsuarioModel::class)->find($usuario['id']);

        return is_array($actualizado)
            ? ['ok' => true, 'errores' => [], 'usuario' => $actualizado]
            : ['ok' => false, 'errores' => ['No pudimos activar la cuenta.']];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function porToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '' || strlen($token) > 80) {
            return null;
        }

        $usuario = model(UsuarioModel::class)
            ->where('invite_token', hash('sha256', $token))
            ->first();
        if (! is_array($usuario)) {
            return null;
        }
        $expira = (string) ($usuario['invite_expires'] ?? '');
        if ($expira === '' || strtotime($expira) < time()) {
            return null;
        }

        return $usuario;
    }

    /**
     * @param array<string, mixed> $admin
     * @param array<string, mixed> $empresa
     *
     * @return array{ok: bool, errores: list<string>}
     */
    public function desactivar(array $admin, array $empresa, int $usuarioId): array
    {
        $usuario = $this->miembroAdmin($admin, $empresa, $usuarioId);
        if ($usuario === null) {
            return ['ok' => false, 'errores' => ['No encontramos a esa persona en tu empresa.']];
        }
        if ((int) $usuario['id'] === (int) $admin['id']) {
            return ['ok' => false, 'errores' => ['No puedes desactivar tu propia cuenta.']];
        }
        if ((string) $usuario['rol'] === 'administrador') {
            return ['ok' => false, 'errores' => ['No se puede desactivar a un administrador.']];
        }

        model(UsuarioModel::class)->update($usuarioId, ['activo' => 0]);

        return ['ok' => true, 'errores' => []];
    }

    /**
     * @param array<string, mixed> $admin
     * @param array<string, mixed> $empresa
     *
     * @return array{ok: bool, errores: list<string>}
     */
    public function activar(array $admin, array $empresa, int $usuarioId): array
    {
        $usuario = $this->miembroAdmin($admin, $empresa, $usuarioId);
        if ($usuario === null) {
            return ['ok' => false, 'errores' => ['No encontramos a esa persona en tu empresa.']];
        }
        if ((string) ($usuario['invite_token'] ?? '') !== '') {
            return ['ok' => false, 'errores' => ['Esa persona aún debe aceptar la invitación y crear su contraseña.']];
        }

        model(UsuarioModel::class)->update($usuarioId, ['activo' => 1]);

        return ['ok' => true, 'errores' => []];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function nuevoToken(): array
    {
        $token  = bin2hex(random_bytes(32));
        $hash   = hash('sha256', $token);
        $expira = date('Y-m-d H:i:s', time() + (self::DIAS_VALIDEZ * 86400));

        return [$token, $hash, $expira];
    }

    /**
     * @param array<string, mixed> $admin
     * @param array<string, mixed> $empresa
     *
     * @return array<string, mixed>|null
     */
    private function miembroAdmin(array $admin, array $empresa, int $usuarioId): ?array
    {
        if (($admin['rol'] ?? '') !== 'administrador' || (int) $admin['empresa_id'] !== (int) $empresa['id']) {
            return null;
        }
        $usuario = model(UsuarioModel::class)->find($usuarioId);
        if (! is_array($usuario) || (int) $usuario['empresa_id'] !== (int) $empresa['id']) {
            return null;
        }

        return $usuario;
    }

    private function enviarCorreo(string $email, string $nombre, string $empresa, string $enlace): bool
    {
        $config = config(EmailConfig::class);
        if (trim((string) $config->fromEmail) === '') {
            return false;
        }

        $mailer = service('email');
        $mailer->setFrom((string) $config->fromEmail, (string) ($config->fromName ?: 'Rindete'));
        $mailer->setTo($email);
        $mailer->setSubject('Invitación a Rindete — ' . $empresa);
        $mailer->setMessage(
            "Hola {$nombre},\n\n"
            . "Te invitaron a rendir gastos en {$empresa} con Rindete.\n"
            . "Abre este enlace para activar tu cuenta (válido " . self::DIAS_VALIDEZ . " días):\n\n"
            . "{$enlace}\n\n"
            . "Si no esperabas este correo, ignóralo.\n"
        );

        try {
            return (bool) $mailer->send(false);
        } catch (\Throwable) {
            return false;
        }
    }
}
