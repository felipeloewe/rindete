<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ConexionModel extends Model
{
    protected $table            = 'conexiones';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['empresa_id', 'nombre', 'destino', 'vigente'];
}
