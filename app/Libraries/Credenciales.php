<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\EmpresaModel;
use App\Models\UsuarioModel;

/**
 * Deja una clave nueva y la manda por correo. Si el correo no sale, se puede volver a la clave anterior.
 */
class Credenciales
{
    /**
     * @param array<string, mixed> $usuario
     *
     * @return array{ok: bool, errores: list<string>, enviada: bool, clave: string, email: string}
     */
    public function renovar(array $usuario, bool $conservarSiNoSale): array
    {
        $vacio = ['ok' => false, 'errores' => [], 'enviada' => false, 'clave' => '', 'email' => ''];
        if ((int) ($usuario['activo'] ?? 0) !== 1) {
            $vacio['errores'][] = 'Habilítala para entrar antes de reenviar la clave.';

            return $vacio;
        }
        $email = strtolower(trim((string) ($usuario['email'] ?? '')));
        if (! IdFiscal::emailValido($email)) {
            $vacio['errores'][] = 'Esa persona no tiene un correo válido.';

            return $vacio;
        }
        $empresa = model(EmpresaModel::class)->find((int) ($usuario['empresa_id'] ?? 0));
        $nombreEmpresa = is_array($empresa) ? (string) ($empresa['nombre'] ?? 'tu empresa') : 'tu empresa';
        $clave = substr(bin2hex(random_bytes(8)), 0, 12);
        $anterior = (string) ($usuario['password'] ?? '');
        $inicial = (int) ($usuario['clave_inicial'] ?? 0);
        model(UsuarioModel::class)->update((int) $usuario['id'], [
            'password'      => password_hash($clave, PASSWORD_DEFAULT),
            'clave_inicial' => 1,
        ]);
        $enviada = (new CorreoAcceso())->claveNueva(
            $email,
            (string) ($usuario['nombre'] ?? ''),
            $nombreEmpresa,
            $clave,
        );
        if (! $enviada && $conservarSiNoSale) {
            model(UsuarioModel::class)->update((int) $usuario['id'], [
                'password'      => $anterior,
                'clave_inicial' => $inicial,
            ]);

            return ['ok' => true, 'errores' => [], 'enviada' => false, 'clave' => '', 'email' => $email];
        }

        return [
            'ok'      => true,
            'errores' => [],
            'enviada' => $enviada,
            'clave'   => $enviada ? '' : $clave,
            'email'   => $email,
        ];
    }
}
