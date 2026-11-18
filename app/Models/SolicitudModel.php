<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class SolicitudModel extends Model
{
    protected $table            = 'solicitudes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'empresa_id', 'usuario_id', 'categoria_id', 'motivo', 'monto', 'moneda', 'estado', 'detalle',
        'turno_usuario_id', 'turno_orden', 'creada', 'resuelta', 'entregada',
    ];
}
