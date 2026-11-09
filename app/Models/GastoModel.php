<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class GastoModel extends Model
{
    protected $table            = 'gastos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'empresa_id', 'usuario_id', 'categoria_id', 'concepto', 'proveedor', 'fecha', 'monto', 'moneda',
        'tipo_doc', 'id_fiscal', 'num_doc', 'codigo_fiscal', 'nota', 'comprobante', 'estado',
        'duplicado', 'fuera_politica', 'sin_comprobante', 'fiscal_incompleto', 'creado',
    ];
}
