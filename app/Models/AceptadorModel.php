<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class AceptadorModel extends Model
{
    protected $table            = 'aceptadores';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['empresa_id', 'orden', 'usuario_id'];
}
