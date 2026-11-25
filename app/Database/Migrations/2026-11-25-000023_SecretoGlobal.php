<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SecretoGlobal extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('medios_conexion')) {
            return;
        }
        if (! $this->db->fieldExists('credencial', 'medios_conexion')) {
            $this->forge->addColumn('medios_conexion', [
                'credencial' => ['type' => 'VARCHAR', 'constraint' => 120, 'default' => ''],
            ]);
        }
        if (! $this->db->fieldExists('client_id', 'medios_conexion')) {
            $this->forge->addColumn('medios_conexion', [
                'client_id' => ['type' => 'VARCHAR', 'constraint' => 120, 'default' => ''],
            ]);
        }
        if (! $this->db->fieldExists('client_secret', 'medios_conexion')) {
            $this->forge->addColumn('medios_conexion', [
                'client_secret' => ['type' => 'TEXT', 'null' => true],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $this->db->query('PRAGMA legacy_alter_table = ON');
        }
        foreach (['client_secret', 'client_id', 'credencial'] as $columna) {
            if ($this->db->tableExists('medios_conexion') && $this->db->fieldExists($columna, 'medios_conexion')) {
                $this->forge->dropColumn('medios_conexion', $columna);
            }
        }
        if ($this->db->DBDriver === 'SQLite3') {
            $this->db->query('PRAGMA legacy_alter_table = OFF');
        }
    }
}
