<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class TarjetaModel extends Model
{
    protected $table            = 'tarjetas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['empresa_id', 'nombre', 'personal', 'monto_aceptado', 'activa'];
}
