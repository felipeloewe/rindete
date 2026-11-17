<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class CuentaModel extends Model
{
    protected $table            = 'cuentas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['empresa_id', 'clase', 'codigo', 'nombre', 'concepto', 'usuario_id', 'activa'];
}
