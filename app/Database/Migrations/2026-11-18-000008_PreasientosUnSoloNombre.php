<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PreasientosUnSoloNombre extends Migration
{
    public function up(): void
    {
        $filas = $this->db->table('empresa_modulos')->where('codigo', 'asientos')->get()->getResultArray();
        foreach ($filas as $fila) {
            $ya = $this->db->table('empresa_modulos')
                ->where('empresa_id', $fila['empresa_id'])
                ->where('codigo', 'preasientos')
                ->countAllResults();
            if ($ya === 0) {
                $this->db->table('empresa_modulos')->where('id', $fila['id'])->update(['codigo' => 'preasientos']);
            } else {
                $this->db->table('empresa_modulos')->where('id', $fila['id'])->delete();
            }
        }
    }

    public function down(): void
    {
    }
}
