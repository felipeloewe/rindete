<?php

declare(strict_types=1);

namespace App\Libraries;

class MaestroSesion
{
    /**
     * @return array<string, mixed>|null
     */
    public function entrar(string $usuario, string $password): ?array
    {
        $fila = $this->porUsuario($usuario);
        if ($fila === null || ! password_verify($password, (string) $fila['password'])) {
            return null;
        }
        $session = session();
        $session->regenerate(true);
        $session->set('maestro_id', (int) $fila['id']);

        return $fila;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function actual(): ?array
    {
        $id = session('maestro_id');
        if ($id === null || $id === '') {
            return null;
        }
        $fila = db_connect()->table('maestros')->where('id', (int) $id)->get()->getRowArray();
        if ($fila === null) {
            $this->salir();

            return null;
        }

        return $fila;
    }

    /**
     * @return array{ok: bool, errores: list<string>}
     */
    public function cambiar(string $actual, string $nueva, string $repetida): array
    {
        $fila = $this->actual();
        if ($fila === null) {
            return ['ok' => false, 'errores' => ['Entra de nuevo.']];
        }
        if (! password_verify($actual, (string) $fila['password'])) {
            return ['ok' => false, 'errores' => ['La contraseña actual no coincide.']];
        }
        if (strlen($nueva) < 8) {
            return ['ok' => false, 'errores' => ['La contraseña nueva debe tener al menos 8 caracteres.']];
        }
        if ($nueva !== $repetida) {
            return ['ok' => false, 'errores' => ['Las contraseñas no coinciden.']];
        }
        if ($nueva === 'demo' || password_verify($nueva, (string) $fila['password'])) {
            return ['ok' => false, 'errores' => ['Elige una contraseña distinta de la inicial.']];
        }
        db_connect()->table('maestros')->where('id', (int) $fila['id'])->update([
            'password'     => password_hash($nueva, PASSWORD_DEFAULT),
            'debe_cambiar' => 0,
        ]);

        return ['ok' => true, 'errores' => []];
    }

    public function salir(): void
    {
        session()->remove('maestro_id');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function porUsuario(string $usuario): ?array
    {
        $usuario = trim($usuario);
        if ($usuario === '') {
            return null;
        }
        foreach (db_connect()->table('maestros')->get()->getResultArray() as $fila) {
            if (strcasecmp((string) $fila['usuario'], $usuario) === 0) {
                return $fila;
            }
        }

        return null;
    }
}
