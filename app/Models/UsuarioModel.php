<?php

declare(strict_types=1);

namespace App\Models;

use App\Libraries\Identidad;
use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table            = 'usuarios';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowCallbacks   = true;
    protected $afterFind        = ['hidratar'];
    protected $allowedFields    = [
        'empresa_id', 'rol', 'activo', 'creado', 'politica_version', 'politica_aceptada_en', 'clave_inicial',
        'puede_rendir_sin_solicitud', 'monto_maximo_rendicion',
    ];

    /**
     * @param array<string, mixed> $evento
     *
     * @return array<string, mixed>
     */
    protected function hidratar(array $evento): array
    {
        $identidad = new Identidad();
        if (($evento['singleton'] ?? false) === true) {
            if (is_array($evento['data'])) {
                $evento['data'] = $identidad->completarPersona($evento['data']);
            }

            return $evento;
        }
        if (is_array($evento['data'])) {
            $evento['data'] = $identidad->completarPersonas($evento['data']);
        }

        return $evento;
    }

    /**
     * @param array<string, mixed>|object|null $row
     */
    public function insert($row = null, bool $returnID = true)
    {
        [$row, $personal, $empresaId] = $this->preparar($row, 'insert');
        $id = parent::insert($row, true);
        if ($id !== false && $personal !== []) {
            try {
                (new Identidad())->ponerPersona((int) $id, $empresaId, $personal);
            } catch (\Throwable $e) {
                parent::delete($id);
                throw $e;
            }
        }

        return $returnID ? $id : $id !== false;
    }

    /**
     * @param array<string, mixed>|object|null $row
     */
    public function update($id = null, $row = null): bool
    {
        [$row, $personal] = $this->preparar($row, 'update');
        $ok = true;
        if (is_array($row) && $row !== []) {
            $ok = parent::update($id, $row);
        } elseif (! is_array($row) && $row !== null) {
            $ok = parent::update($id, $row);
        }
        if ($ok && $personal !== [] && is_numeric($id)) {
            (new Identidad())->fundirPersona((int) $id, $personal);
        }

        return $ok;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function porEmail(string $email): ?array
    {
        return (new Identidad())->personaPorEmail($email);
    }

    /**
     * @param array<string, mixed>|object|null $row
     *
     * @return array{0: array<string, mixed>|object|null, 1: array<string, mixed>, 2?: int}
     */
    private function preparar($row, string $metodo): array
    {
        if (isset($this->tempData['data'])) {
            if ($row === null) {
                $row = $this->tempData['data'];
            } else {
                $row = $this->transformDataToArray($row, $metodo);
                $row = array_merge($this->tempData['data'], $row);
            }
        }
        $this->escape   = $this->tempData['escape'] ?? [];
        $this->tempData = [];
        $personal       = [];
        $empresaId      = 0;
        if (is_array($row)) {
            $empresaId = (int) ($row['empresa_id'] ?? 0);
            $personal  = (new Identidad())->separar($row, Identidad::PERSONA);
        }

        return $metodo === 'insert' ? [$row, $personal, $empresaId] : [$row, $personal];
    }
}
