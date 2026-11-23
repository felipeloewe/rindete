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
        'origen', 'fondo_id', 'rendicion_previa_id', 'monto_por_rendir', 'monto_adicional',
        'turno_usuario_id', 'turno_orden', 'monto_maximo', 'cuenta_viatico_id', 'cuenta_asignacion_id',
        'reembolso',
    ];
}
