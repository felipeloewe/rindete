<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class RendicionModel extends Model
{
    protected $table            = 'rendiciones';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'empresa_id', 'usuario_id', 'titulo', 'nota', 'estado', 'creada', 'enviada',
    ];
}
