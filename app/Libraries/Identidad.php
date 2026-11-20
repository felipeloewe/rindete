<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

/**
 * Nombre, correo, RUT, cuenta y clave viven aquí.
 * La base de operación guarda el id de la persona y de la empresa.
 */
class Identidad
{
    /** @var list<string> */
    public const PERSONA = ['nombre', 'email', 'id_fiscal', 'cuenta_banco', 'banco_destino', 'password'];

    /** @var list<string> */
    public const EMPRESA = ['nombre', 'id_fiscal', 'cuenta_cargo'];

    /**
     * @param array<string, mixed> $fila
     *
     * @return array<string, mixed>
     */
    public function completarPersona(array $fila): array
    {
        $id = (int) ($fila['id'] ?? 0);
        if ($id < 1) {
            return $fila;
        }
        $propia = $this->persona($id);
        if ($propia === null) {
            return $fila;
        }

        return $this->mezclar($fila, $propia);
    }

    /**
     * @param list<array<string, mixed>> $filas
     *
     * @return list<array<string, mixed>>
     */
    public function completarPersonas(array $filas): array
    {
        $ids = [];
        foreach ($filas as $fila) {
            $id = (int) ($fila['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        $mapa = $this->personas($ids);
        foreach ($filas as $i => $fila) {
            $id = (int) ($fila['id'] ?? 0);
            if (isset($mapa[$id])) {
                $filas[$i] = $this->mezclar($fila, $mapa[$id]);
            }
        }

        return $filas;
    }

    /**
     * @param array<string, mixed> $fila
     *
     * @return array<string, mixed>
     */
    public function completarEmpresa(array $fila): array
    {
        $id = (int) ($fila['id'] ?? 0);
        if ($id < 1) {
            return $fila;
        }
        $propia = $this->organizacion($id);
        if ($propia === null) {
            return $fila;
        }
        foreach (self::EMPRESA as $clave) {
            $fila[$clave] = $propia[$clave] ?? '';
        }

        return $fila;
    }

    /**
     * @param list<array<string, mixed>> $filas
     *
     * @return list<array<string, mixed>>
     */
    public function completarEmpresas(array $filas): array
    {
        foreach ($filas as $i => $fila) {
            $filas[$i] = $this->completarEmpresa($fila);
        }

        return $filas;
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function ponerPersona(int $usuarioId, int $empresaId, array $datos): void
    {
        if ($usuarioId < 1) {
            return;
        }
        if ($empresaId < 1) {
            $empresaId = $this->empresaDeUsuario($usuarioId);
        }
        $fila = [
            'usuario_id'    => $usuarioId,
            'empresa_id'    => $empresaId,
            'nombre'        => trim((string) ($datos['nombre'] ?? '')),
            'email'         => $this->correo($datos['email'] ?? ''),
            'id_fiscal'     => $this->fiscal($datos['id_fiscal'] ?? null),
            'cuenta_banco'  => trim((string) ($datos['cuenta_banco'] ?? '')),
            'banco_destino' => trim((string) ($datos['banco_destino'] ?? '')),
            'password'      => (string) ($datos['password'] ?? ''),
        ];
        $this->conexion()->table('personas')->insert($fila);
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function fundirPersona(int $usuarioId, array $datos): void
    {
        if ($usuarioId < 1 || $datos === []) {
            return;
        }
        $actual = $this->persona($usuarioId);
        if ($actual === null) {
            $this->ponerPersona($usuarioId, 0, $datos);

            return;
        }
        $cambios = [];
        foreach (self::PERSONA as $clave) {
            if (! array_key_exists($clave, $datos)) {
                continue;
            }
            $cambios[$clave] = $clave === 'id_fiscal'
                ? $this->fiscal($datos[$clave])
                : ($clave === 'email' ? $this->correo($datos[$clave]) : $datos[$clave]);
        }
        if ($cambios === []) {
            return;
        }
        $this->conexion()->table('personas')->where('usuario_id', $usuarioId)->update($cambios);
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function ponerOrganizacion(int $empresaId, array $datos): void
    {
        if ($empresaId < 1) {
            return;
        }
        $pais = $datos['pais'] ?? $this->paisDeEmpresa($empresaId);
        $this->conexion()->table('organizaciones')->insert([
            'empresa_id'   => $empresaId,
            'pais'         => (string) $pais,
            'nombre'       => trim((string) ($datos['nombre'] ?? '')),
            'id_fiscal'    => (string) ($this->fiscal($datos['id_fiscal'] ?? '') ?? ''),
            'cuenta_cargo' => trim((string) ($datos['cuenta_cargo'] ?? '')),
        ]);
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function fundirOrganizacion(int $empresaId, array $datos): void
    {
        if ($empresaId < 1 || $datos === []) {
            return;
        }
        $actual = $this->organizacion($empresaId);
        if ($actual === null) {
            $this->ponerOrganizacion($empresaId, $datos);

            return;
        }
        $cambios = [];
        foreach (self::EMPRESA as $clave) {
            if (! array_key_exists($clave, $datos)) {
                continue;
            }
            $cambios[$clave] = $clave === 'id_fiscal'
                ? (string) ($this->fiscal($datos[$clave]) ?? '')
                : trim((string) $datos[$clave]);
        }
        if (array_key_exists('pais', $datos)) {
            $cambios['pais'] = (string) $datos['pais'];
        }
        if ($cambios === []) {
            return;
        }
        $this->conexion()->table('organizaciones')->where('empresa_id', $empresaId)->update($cambios);
    }

    public function olvidarPersona(int $usuarioId): void
    {
        if ($usuarioId < 1) {
            return;
        }
        $this->conexion()->table('personas')->where('usuario_id', $usuarioId)->delete();
    }

    public function olvidarOrganizacion(int $empresaId): void
    {
        if ($empresaId < 1) {
            return;
        }
        $this->conexion()->table('organizaciones')->where('empresa_id', $empresaId)->delete();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function personaPorEmail(string $email): ?array
    {
        $fila = $this->conexion()->table('personas')->where('email', $this->correo($email))->get()->getRowArray();
        if ($fila === null) {
            return null;
        }

        return model(\App\Models\UsuarioModel::class)->find((int) $fila['usuario_id']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function organizacionPorFiscal(string $pais, string $idFiscal): ?array
    {
        $fila = $this->conexion()->table('organizaciones')
            ->where('pais', $pais)
            ->where('id_fiscal', (string) ($this->fiscal($idFiscal) ?? ''))
            ->get()
            ->getRowArray();
        if ($fila === null) {
            return null;
        }

        return model(\App\Models\EmpresaModel::class)->find((int) $fila['empresa_id']);
    }

    /**
     * @param list<int> $ids
     *
     * @return array<int, string>
     */
    public function nombres(array $ids): array
    {
        $mapa = [];
        foreach ($this->personas($ids) as $id => $fila) {
            $mapa[$id] = (string) ($fila['nombre'] ?? '');
        }

        return $mapa;
    }

    /**
     * @param array<string, mixed> $fila
     *
     * @return array<string, mixed>
     */
    public function separar(array &$fila, array $claves): array
    {
        $sacado = [];
        foreach ($claves as $clave) {
            if (array_key_exists($clave, $fila)) {
                $sacado[$clave] = $fila[$clave];
                unset($fila[$clave]);
            }
        }

        return $sacado;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function persona(int $usuarioId): ?array
    {
        $fila = $this->conexion()->table('personas')->where('usuario_id', $usuarioId)->get()->getRowArray();

        return $fila === null ? null : $fila;
    }

    /**
     * @param list<int> $ids
     *
     * @return array<int, array<string, mixed>>
     */
    private function personas(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }
        $mapa = [];
        foreach ($this->conexion()->table('personas')->whereIn('usuario_id', $ids)->get()->getResultArray() as $fila) {
            $mapa[(int) $fila['usuario_id']] = $fila;
        }

        return $mapa;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function organizacion(int $empresaId): ?array
    {
        $fila = $this->conexion()->table('organizaciones')->where('empresa_id', $empresaId)->get()->getRowArray();

        return $fila === null ? null : $fila;
    }

    /**
     * @param array<string, mixed> $fila
     * @param array<string, mixed> $propia
     *
     * @return array<string, mixed>
     */
    private function mezclar(array $fila, array $propia): array
    {
        foreach (self::PERSONA as $clave) {
            $fila[$clave] = $propia[$clave] ?? ($clave === 'id_fiscal' ? null : '');
        }

        return $fila;
    }

    private function empresaDeUsuario(int $usuarioId): int
    {
        $fila = db_connect()->table('usuarios')->select('empresa_id')->where('id', $usuarioId)->get()->getRowArray();

        return (int) ($fila['empresa_id'] ?? 0);
    }

    private function paisDeEmpresa(int $empresaId): string
    {
        $fila = db_connect()->table('empresas')->select('pais')->where('id', $empresaId)->get()->getRowArray();

        return (string) ($fila['pais'] ?? '');
    }

    private function correo(mixed $valor): string
    {
        return strtolower(trim((string) $valor));
    }

    private function fiscal(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }
        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }

    private function conexion(): BaseConnection
    {
        $base = config(\Config\Database::class)->identidad['database'] ?? '';
        if ($base === '') {
            throw new \RuntimeException('La base de identidades no está configurada.');
        }

        return db_connect('identidad');
    }
}
