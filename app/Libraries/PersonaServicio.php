<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\UsuarioModel;

class PersonaServicio
{
    /**
     * @param array<string, mixed> $empresa
     * @param array<string, mixed> $input
     *
     * @return array{ok: bool, errores: list<string>, id?: int}
     */
    public function crear(array $empresa, array $input): array
    {
        $nombre   = trim((string) ($input['nombre'] ?? ''));
        $email    = strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');
        $errores  = [];
        if ($nombre === '' || strlen($nombre) > 80) {
            $errores[] = 'Escribe el nombre de la persona.';
        }
        if (! IdFiscal::emailValido($email)) {
            $errores[] = 'El correo no es válido.';
        } elseif (model(UsuarioModel::class)->porEmail($email) !== null) {
            $errores[] = 'Ese correo ya tiene una cuenta.';
        }
        if (strlen($password) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        $monto = $this->monto((string) ($input['monto_maximo'] ?? ''), $errores);
        $fiscal = $this->documento($empresa, (string) ($input['id_fiscal'] ?? ''), $errores);
        $banco = $this->cuentaBanco((string) ($input['cuenta_banco'] ?? ''), $errores);
        $destino = $this->bancoDestino((string) ($input['banco_destino'] ?? ''), $errores);
        $telefono = $this->telefono($empresa, $input, $errores);
        $cupo  = (int) ($empresa['cupo_usuarios'] ?? Planes::USUARIOS);
        if (! Planes::puedeSumarUsuario(Planes::rendidores((int) $empresa['id']), $cupo)) {
            $errores[] = Planes::avisoCupo($empresa);
        }
        if ($errores !== []) {
            return ['ok' => false, 'errores' => $errores];
        }

        $id = model(UsuarioModel::class)->insert([
            'empresa_id'                 => (int) $empresa['id'],
            'nombre'                      => $nombre,
            'email'                       => $email,
            'id_fiscal'                   => $fiscal,
            'cuenta_banco'                => $banco,
            'banco_destino'               => $destino,
            'telefono'                    => $telefono,
            'password'                    => password_hash($password, PASSWORD_DEFAULT),
            'rol'                         => 'rendidor',
            'activo'                      => 1,
            'puede_rendir_sin_solicitud' => ! empty($input['puede_rendir_sin_solicitud']) ? 1 : 0,
            'rinde'                      => 1,
            'monto_maximo_rendicion'     => $monto,
            'creado'                      => date('Y-m-d H:i:s'),
        ] + PoliticaDatos::marca(), true);
        ParametrosBase::sumarUsuario((int) $empresa['id'], (int) $id);

        return ['ok' => true, 'errores' => [], 'id' => (int) $id];
    }

    /**
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $persona
     * @param array<string, mixed> $input
     *
     * @return array{ok: bool, errores: list<string>}
     */
    public function guardar(array $actor, array $persona, array $input): array
    {
        if ((int) $actor['empresa_id'] !== (int) $persona['empresa_id']) {
            return ['ok' => false, 'errores' => ['Esa persona no es de esta empresa.']];
        }
        $empresa = model(\App\Models\EmpresaModel::class)->find((int) $persona['empresa_id']);
        if (! is_array($empresa)) {
            return ['ok' => false, 'errores' => ['No encontramos la empresa.']];
        }

        $errores = [];
        $monto   = $this->monto((string) ($input['monto_maximo'] ?? ''), $errores);
        $fiscal  = $this->documento($empresa, (string) ($input['id_fiscal'] ?? ''), $errores);
        $banco   = $this->cuentaBanco((string) ($input['cuenta_banco'] ?? ''), $errores);
        $destino = array_key_exists('banco_destino', $input)
            ? $this->bancoDestino((string) $input['banco_destino'], $errores)
            : null;
        $telefono = array_key_exists('telefono', $input)
            ? $this->telefono($empresa, $input, $errores, (int) $persona['id'])
            : null;
        $datos   = [
            'puede_rendir_sin_solicitud' => ! empty($input['puede_rendir_sin_solicitud']) ? 1 : 0,
            'monto_maximo_rendicion'     => $monto,
            'id_fiscal'                  => $fiscal,
            'cuenta_banco'               => $banco,
            'activo'                      => (int) $actor['id'] === (int) $persona['id'] ? 1 : (! empty($input['activo']) ? 1 : 0),
        ];
        $clave = (string) ($input['password'] ?? '');
        if ($clave !== '') {
            if (strlen($clave) < 8) {
                $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
            } else {
                $datos['password'] = password_hash($clave, PASSWORD_DEFAULT);
            }
        }
        if ($destino !== null) {
            $datos['banco_destino'] = $destino;
        }
        if ($telefono !== null) {
            $datos['telefono'] = $telefono;
        }
        if ($errores !== []) {
            return ['ok' => false, 'errores' => $errores];
        }

        model(UsuarioModel::class)->update($persona['id'], $datos);

        return ['ok' => true, 'errores' => []];
    }

    /**
     * @param list<string> $errores
     */
    /**
     * @param array<string, mixed> $empresa
     * @param array<string, mixed> $input
     * @param list<string> $errores
     */
    private function telefono(array $empresa, array $input, array &$errores, int $excepto = 0): string
    {
        if (! array_key_exists('telefono', $input)) {
            return '';
        }
        $normal = Identidad::telefono((string) $input['telefono']);
        if (! Identidad::telefonoValido($normal)) {
            $errores[] = 'El teléfono de envío no es válido.';

            return '';
        }
        if ($normal !== '' && (new Identidad())->telefonoOcupado((int) $empresa['id'], $normal, $excepto)) {
            $errores[] = 'Ese teléfono ya está en otra persona.';
        }

        return $normal;
    }

    private function monto(string $raw, array &$errores): ?float
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $valor = Montos::parse($raw);
        if ($valor === null) {
            $errores[] = 'El tope propio tiene que ser un monto.';

            return null;
        }

        return $valor;
    }

    /**
     * @param array<string, mixed> $empresa
     * @param list<string> $errores
     */
    private function documento(array $empresa, string $raw, array &$errores): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $fiscal = IdFiscal::validar((string) $empresa['pais'], $raw);
        if (! $fiscal['ok']) {
            $errores[] = $fiscal['error'];

            return null;
        }

        return $fiscal['valor'];
    }

    /**
     * @param list<string> $errores
     */
    private function cuentaBanco(string $raw, array &$errores): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (strlen($raw) > 40 || preg_match('/^[0-9 -]+$/', $raw) !== 1) {
            $errores[] = 'La cuenta del banco solo lleva números y guiones.';

            return '';
        }
        $plana = (string) preg_replace('/[^0-9A-Za-z]/', '', $raw);
        if (strlen($plana) > 18) {
            $errores[] = 'La cuenta del banco admite hasta 18 caracteres.';

            return '';
        }

        return $raw;
    }

    /**
     * @param list<string> $errores
     */
    private function bancoDestino(string $raw, array &$errores): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (! BancosChile::existe($raw)) {
            $errores[] = 'Elige el banco o el emisor TPPF.';

            return '';
        }

        return $raw;
    }
}
