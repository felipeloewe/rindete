<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class EmpresaModel extends Model
{
    protected $table            = 'empresas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'nombre', 'pais', 'id_fiscal', 'plan', 'cupo_usuarios', 'cupo_bytes', 'creado',
        'parametros_sembrados', 'monto_maximo_rendicion', 'detener_monto', 'detener_tipo', 'detener_habilitacion',
        'preasiento_agrupacion', 'preasiento_auxiliar', 'preasiento_cuenta_id', 'preasiento_salida_id',
    ];
}
