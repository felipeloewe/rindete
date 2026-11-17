<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class TipoDocumentoModel extends Model
{
    protected $table            = 'tipos_documento';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['empresa_id', 'nombre', 'monto_aceptado', 'sin_comprobante', 'activo'];
}
