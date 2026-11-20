<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class EmpresaModel extends Model
{
    protected $table            = 'empresas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'nombre',
        'pais',
        'id_fiscal',
        'detectar_duplicados',
        'dias_duplicado',
        'comprobante_desde',
        'timezone',
        'creado',
    ];
}
