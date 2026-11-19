<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class FondoModel extends Model
{
    protected $table            = 'fondos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'empresa_id', 'usuario_id', 'solicitud_id', 'motivo', 'monto', 'saldo', 'moneda', 'estado', 'origen', 'creado',
    ];
}
