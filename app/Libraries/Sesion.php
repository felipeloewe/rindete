<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\EmpresaModel;
use App\Models\UsuarioModel;

class Sesion
{
    public function entrar(array $usuario): void
    {
        $session = session();
        $session->regenerate(true);
        $session->set('usuario_id', (int) $usuario['id']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function usuario(): ?array
    {
        $id = session('usuario_id');
        if ($id === null || $id === '') {
            return null;
        }

        $usuario = model(UsuarioModel::class)->find((int) $id);
        if (! is_array($usuario) || (int) $usuario['activo'] !== 1) {
            $this->salir();

            return null;
        }

        return $usuario;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function empresa(): ?array
    {
        $usuario = $this->usuario();
        if ($usuario === null) {
            return null;
        }

        $empresa = model(EmpresaModel::class)->find((int) $usuario['empresa_id']);

        return is_array($empresa) ? $empresa : null;
    }

    public function salir(): void
    {
        session()->destroy();
    }
}
