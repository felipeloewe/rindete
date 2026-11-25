<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class MedioModel extends Model
{
    protected $table            = 'medios_conexion';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['empresa_id', 'proveedor', 'clave', 'secreto', 'token_consulta', 'credencial', 'client_id', 'client_secret', 'vigente'];
}
