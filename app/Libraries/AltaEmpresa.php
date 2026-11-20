<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\EmpresaModel;
use App\Models\UsuarioModel;

class AltaEmpresa
{
    /**
     * @param array{pais?: string, empresa?: string, id_fiscal?: string, nombre?: string, email?: string, password?: string} $input
     *
     * @return array{ok: bool, errores: list<string>, usuario?: array<string, mixed>}
     */
    public function registrar(array $input): array
    {
        $pais     = strtoupper(trim((string) ($input['pais'] ?? '')));
        $empresa  = trim((string) ($input['empresa'] ?? ''));
        $nombre   = trim((string) ($input['nombre'] ?? ''));
        $email    = strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');
        $fiscal   = IdFiscal::validar($pais, (string) ($input['id_fiscal'] ?? ''));

        $errores = [];
        if (Paises::pais($pais) === null) {
            $errores[] = 'Elige un país de la lista.';
        }
        if ($empresa === '' || $nombre === '' || $email === '') {
            $errores[] = 'Completa empresa, nombre y correo.';
        }
        if (strlen($empresa) > 120 || strlen($nombre) > 80) {
            $errores[] = 'El nombre de la empresa o de la persona es demasiado largo.';
        }
        if ($email !== '' && ! IdFiscal::emailValido($email)) {
            $errores[] = 'El correo no es válido.';
        }
        if (! $fiscal['ok']) {
            $errores[] = $fiscal['error'];
        }
        if (strlen($password) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($errores !== []) {
            return ['ok' => false, 'errores' => $errores];
        }

        $empresas = model(EmpresaModel::class);
        $usuarios = model(UsuarioModel::class);
        if ($empresas->porFiscal($pais, $fiscal['valor']) !== null) {
            $documento = Paises::pais($pais)['id_fiscal'];

            return ['ok' => false, 'errores' => ['Ese ' . $documento . ' ya tiene una cuenta. Contacta al administrador de la empresa para que te dé acceso.']];
        }
        if ($usuarios->porEmail($email) !== null) {
            return ['ok' => false, 'errores' => ['Ese correo ya tiene una cuenta.']];
        }

        $ahora     = date('Y-m-d H:i:s');
        $db        = db_connect();
        $empresaId = 0;
        $usuarioId = 0;
        try {
            $db->transException(true)->transStart();
            $empresaId = $empresas->insert([
                'nombre'    => $empresa,
                'pais'      => $pais,
                'id_fiscal' => $fiscal['valor'],
                'creado'    => $ahora,
            ] + Planes::alta(), true);
            $usuarioId = $usuarios->insert([
                'empresa_id' => $empresaId,
                'nombre'     => $nombre,
                'email'      => $email,
                'password'   => password_hash($password, PASSWORD_DEFAULT),
                'rol'                         => 'administrador',
                'activo'                      => 1,
                'puede_rendir_sin_solicitud' => 1,
                'creado'                      => $ahora,
            ], true);
            CategoriasBase::sembrar((int) $empresaId, Paises::pais($pais)['moneda']);
            ParametrosBase::sembrar(['id' => (int) $empresaId, 'pais' => $pais]);
            $db->transComplete();
        } catch (\Throwable $e) {
            (new Identidad())->olvidarOrganizacion((int) $empresaId);
            (new Identidad())->olvidarPersona((int) $usuarioId);
            throw $e;
        }

        $usuario = $usuarios->find($usuarioId);
        if (! is_array($usuario)) {
            return ['ok' => false, 'errores' => ['No pudimos crear la empresa.']];
        }

        return ['ok' => true, 'errores' => [], 'usuario' => $usuario];
    }
}
