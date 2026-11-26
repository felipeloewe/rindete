<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\EmpresaModel;
use App\Models\UsuarioModel;

/**
 * El superadmin abre la empresa y su administrador. La persona acepta las políticas al entrar.
 */
class Incorporacion
{
    /**
     * @param array<string, mixed> $input
     *
     * @return array{ok: bool, errores: list<string>, clave?: string, email?: string, nombre?: string, empresa?: string}
     */
    public function abrir(array $input): array
    {
        $pais    = strtoupper(trim((string) ($input['pais'] ?? '')));
        $empresa = trim((string) ($input['empresa'] ?? ''));
        $nombre  = trim((string) ($input['nombre'] ?? ''));
        $email   = strtolower(trim((string) ($input['email'] ?? '')));
        $fiscal  = IdFiscal::validar($pais, (string) ($input['id_fiscal'] ?? ''));
        $errores = [];
        if (Paises::pais($pais) === null) {
            $errores[] = 'Elige un país de la lista.';
        }
        if ($empresa === '' || strlen($empresa) > 120) {
            $errores[] = 'Escribe el nombre de la empresa.';
        }
        if ($nombre === '' || strlen($nombre) > 80) {
            $errores[] = 'Escribe el nombre del administrador.';
        }
        if (! IdFiscal::emailValido($email)) {
            $errores[] = 'El correo no es válido.';
        }
        if (! $fiscal['ok']) {
            $errores[] = $fiscal['error'];
        }
        $comercial = $this->comercial($pais, $input, $errores);
        if ($errores !== []) {
            return ['ok' => false, 'errores' => $errores];
        }
        if (model(EmpresaModel::class)->porFiscal($pais, $fiscal['valor']) !== null) {
            $documento = Paises::pais($pais)['id_fiscal'];

            return ['ok' => false, 'errores' => ['Ese ' . $documento . ' ya tiene una cuenta.']];
        }
        if (model(UsuarioModel::class)->porEmail($email) !== null) {
            return ['ok' => false, 'errores' => ['Ese correo ya tiene una cuenta.']];
        }

        $clave     = $this->clave();
        $ahora     = date('Y-m-d H:i:s');
        $db        = db_connect();
        $empresaId = 0;
        $usuarioId = 0;
        try {
            $db->transException(true)->transStart();
            $empresaId = model(EmpresaModel::class)->insert([
                'nombre'    => $empresa,
                'pais'      => $pais,
                'id_fiscal' => $fiscal['valor'],
                'creado'    => $ahora,
            ] + $comercial, true);
            $usuarioId = model(UsuarioModel::class)->insert([
                'empresa_id'                 => $empresaId,
                'nombre'                     => $nombre,
                'email'                      => $email,
                'password'                   => password_hash($clave, PASSWORD_DEFAULT),
                'rol'                        => 'administrador',
                'activo'                     => 1,
                'puede_rendir_sin_solicitud' => 1,
                'rinde'                      => 0,
                'clave_inicial'              => 1,
                'creado'                     => $ahora,
            ], true);
            CategoriasBase::sembrar((int) $empresaId, Paises::pais($pais)['moneda']);
            ParametrosBase::sembrar(['id' => (int) $empresaId, 'pais' => $pais]);
            $db->transComplete();
        } catch (\Throwable $e) {
            (new Identidad())->olvidarOrganizacion((int) $empresaId);
            (new Identidad())->olvidarPersona((int) $usuarioId);
            throw $e;
        }

        return [
            'ok'      => true,
            'errores' => [],
            'clave'   => $clave,
            'email'   => $email,
            'nombre'  => $nombre,
            'empresa' => $empresa,
        ];
    }

    /**
     * @return array{ok: bool, errores: list<string>, clave?: string, email?: string, nombre?: string, empresa?: string}
     */
    public function administrador(int $empresaId, string $nombre, string $email): array
    {
        $empresa = model(EmpresaModel::class)->find($empresaId);
        if (! is_array($empresa)) {
            return ['ok' => false, 'errores' => ['Esa empresa no está.']];
        }
        $nombre  = trim($nombre);
        $email   = strtolower(trim($email));
        $errores = [];
        if ($nombre === '' || strlen($nombre) > 80) {
            $errores[] = 'Escribe el nombre del administrador.';
        }
        if (! IdFiscal::emailValido($email)) {
            $errores[] = 'El correo no es válido.';
        } elseif (model(UsuarioModel::class)->porEmail($email) !== null) {
            $errores[] = 'Ese correo ya tiene una cuenta.';
        }
        if ($errores !== []) {
            return ['ok' => false, 'errores' => $errores];
        }

        $clave = $this->clave();
        model(UsuarioModel::class)->insert([
            'empresa_id'                 => $empresaId,
            'nombre'                     => $nombre,
            'email'                      => $email,
            'password'                   => password_hash($clave, PASSWORD_DEFAULT),
            'rol'                        => 'administrador',
            'activo'                     => 1,
            'puede_rendir_sin_solicitud' => 1,
            'rinde'                      => 0,
            'clave_inicial'              => 1,
            'creado'                     => date('Y-m-d H:i:s'),
        ], true);

        return [
            'ok'      => true,
            'errores' => [],
            'clave'   => $clave,
            'email'   => $email,
            'nombre'  => $nombre,
            'empresa' => (string) ($empresa['nombre'] ?? ''),
        ];
    }

    /**
     * @return array{ok: bool, errores: list<string>, aviso?: string}
     */
    public function marcarRendidor(int $usuarioId, bool $rinde): array
    {
        $persona = model(UsuarioModel::class)->find($usuarioId);
        if (! is_array($persona)) {
            return ['ok' => false, 'errores' => ['Esa persona no está.']];
        }
        if ((string) ($persona['rol'] ?? '') !== 'administrador') {
            return ['ok' => false, 'errores' => ['Solo se marca al administrador. Quien rinde ya cuenta en el plan.']];
        }
        $quiere = $rinde ? 1 : 0;
        if ((int) ($persona['rinde'] ?? 0) === $quiere) {
            return [
                'ok'      => true,
                'errores' => [],
                'aviso'   => $quiere === 1 ? 'Ya está marcado como usuario rendidor.' : 'Sigue como solo administrador.',
            ];
        }
        if ($quiere === 1) {
            $empresa = model(EmpresaModel::class)->find((int) $persona['empresa_id']);
            if (! is_array($empresa)) {
                return ['ok' => false, 'errores' => ['Esa empresa no está.']];
            }
            $cupo = (int) ($empresa['cupo_usuarios'] ?? Planes::USUARIOS);
            if (! Planes::puedeSumarUsuario(Planes::rendidores((int) $empresa['id']), $cupo)) {
                return ['ok' => false, 'errores' => [Planes::avisoCupo($empresa)]];
            }
        }
        model(UsuarioModel::class)->update($usuarioId, ['rinde' => $quiere]);

        return [
            'ok'      => true,
            'errores' => [],
            'aviso'   => $quiere === 1
                ? 'El administrador queda como usuario rendidor y ocupa un cupo.'
                : 'El administrador queda solo como administrador y libera el cupo.',
        ];
    }

    public function vigencia(int $empresaId, bool $vigente): bool
    {
        $empresa = model(EmpresaModel::class)->find($empresaId);
        if (! is_array($empresa)) {
            return false;
        }

        return model(EmpresaModel::class)->update($empresaId, ['vigente' => $vigente ? 1 : 0]);
    }

    /**
     * @param array<string, mixed> $input
     * @param list<string>         $errores
     *
     * @return array<string, mixed>
     */
    private function comercial(string $pais, array $input, array &$errores): array
    {
        $datosPais = Paises::pais($pais);
        $moneda    = is_array($datosPais) ? $datosPais['moneda'] : 'CLP';
        $plan      = (string) ($input['plan'] ?? '');
        if ($plan === 'free') {
            return Planes::alta() + [
                'cobro_plan'         => '',
                'cobro_monto'        => 0,
                'cobro_moneda'       => $moneda,
                'cobro_periodicidad' => '',
                'vigente'            => 1,
            ];
        }
        if ($plan !== 'cobro') {
            $errores[] = 'Elige Plan Free o un plan de cobro.';

            return [];
        }
        $nombre = trim((string) ($input['cobro_plan'] ?? ''));
        $monto  = Montos::parse((string) ($input['cobro_monto'] ?? ''));
        $cada   = (string) ($input['cobro_periodicidad'] ?? '');
        $cupo   = (int) ($input['cupo_usuarios'] ?? 0);
        if ($nombre === '' || strlen($nombre) > 80) {
            $errores[] = 'Escribe el nombre del plan.';
        }
        if ($monto === null || $monto <= 0) {
            $errores[] = 'El monto del cobro tiene que ser mayor que cero.';
        }
        if (! in_array($cada, ['mensual', 'anual'], true)) {
            $errores[] = 'Elige si el cobro es mensual o anual.';
        }
        if ($cupo < 1 || $cupo > 500) {
            $errores[] = 'El cupo de usuarios del plan va de 1 a 500.';
        }
        if ($errores !== []) {
            return [];
        }

        return [
            'plan'               => 'cobro',
            'cupo_usuarios'      => $cupo,
            'cupo_bytes'         => Planes::BYTES,
            'cobro_plan'         => $nombre,
            'cobro_monto'        => $monto,
            'cobro_moneda'       => $moneda,
            'cobro_periodicidad' => $cada,
            'vigente'            => 1,
        ];
    }

    private function clave(): string
    {
        return substr(bin2hex(random_bytes(8)), 0, 12);
    }
}
