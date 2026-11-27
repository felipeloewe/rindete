<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class LugaresDePago extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('empresas')) {
            return;
        }
        $columnas = [
            'usa_centro'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'usa_sucursal' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'usa_concepto' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ];
        foreach ($columnas as $nombre => $campo) {
            if (! $this->db->fieldExists($nombre, 'empresas')) {
                $this->forge->addColumn('empresas', [$nombre => $campo]);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $this->db->query('PRAGMA legacy_alter_table = ON');
        }
        if ($this->db->tableExists('empresas')) {
            foreach (['usa_concepto', 'usa_sucursal', 'usa_centro'] as $nombre) {
                if ($this->db->fieldExists($nombre, 'empresas')) {
                    $this->forge->dropColumn('empresas', $nombre);
                }
            }
        }
        if ($this->db->DBDriver === 'SQLite3') {
            $this->db->query('PRAGMA legacy_alter_table = OFF');
        }
    }
}
