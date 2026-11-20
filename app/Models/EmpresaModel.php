<?php

declare(strict_types=1);

namespace App\Models;

use App\Libraries\Identidad;
use CodeIgniter\Model;

class EmpresaModel extends Model
{
    protected $table            = 'empresas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowCallbacks   = true;
    protected $afterFind        = ['hidratar'];
    protected $allowedFields    = [
        'pais', 'plan', 'cupo_usuarios', 'cupo_bytes', 'creado', 'politica_version', 'politica_aceptada_en',
        'parametros_sembrados', 'monto_maximo_rendicion', 'detener_monto', 'detener_tipo', 'detener_habilitacion',
        'preasiento_agrupacion', 'preasiento_auxiliar', 'preasiento_cuenta_id', 'preasiento_salida_id', 'preasiento_liquidacion_id',
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
                $evento['data'] = $identidad->completarEmpresa($evento['data']);
            }

            return $evento;
        }
        if (is_array($evento['data'])) {
            $evento['data'] = $identidad->completarEmpresas($evento['data']);
        }

        return $evento;
    }

    /**
     * @param array<string, mixed>|object|null $row
     */
    public function insert($row = null, bool $returnID = true)
    {
        [$row, $propia] = $this->preparar($row, 'insert');
        $id = parent::insert($row, true);
        if ($id !== false && $propia !== []) {
            if (is_array($row) && isset($row['pais'])) {
                $propia['pais'] = $row['pais'];
            }
            try {
                (new Identidad())->ponerOrganizacion((int) $id, $propia);
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
        [$row, $propia] = $this->preparar($row, 'update');
        if (is_array($row) && isset($row['pais'])) {
            $propia['pais'] = $row['pais'];
        }
        $ok = true;
        if (is_array($row) && $row !== []) {
            $ok = parent::update($id, $row);
        } elseif (! is_array($row) && $row !== null) {
            $ok = parent::update($id, $row);
        }
        if ($ok && $propia !== [] && is_numeric($id)) {
            (new Identidad())->fundirOrganizacion((int) $id, $propia);
        }

        return $ok;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function porFiscal(string $pais, string $idFiscal): ?array
    {
        return (new Identidad())->organizacionPorFiscal($pais, $idFiscal);
    }

    /**
     * @param array<string, mixed>|object|null $row
     *
     * @return array{0: array<string, mixed>|object|null, 1: array<string, mixed>}
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
        $propia         = [];
        if (is_array($row)) {
            $propia = (new Identidad())->separar($row, Identidad::EMPRESA);
        }

        return [$row, $propia];
    }
}
