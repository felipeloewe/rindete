<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class TableroCierre extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('reembolso', 'rendiciones')) {
            $this->forge->addColumn('rendiciones', [
                'reembolso' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => ''],
            ]);
        }
        if ($this->db->tableExists('entradas') && ! $this->db->fieldExists('lectura', 'entradas')) {
            $this->forge->addColumn('entradas', [
                'lectura' => ['type' => 'TEXT', 'null' => true],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $this->db->query('PRAGMA legacy_alter_table = ON');
        }
        if ($this->db->fieldExists('reembolso', 'rendiciones')) {
            $this->forge->dropColumn('rendiciones', 'reembolso');
        }
        if ($this->db->tableExists('entradas') && $this->db->fieldExists('lectura', 'entradas')) {
            $this->forge->dropColumn('entradas', 'lectura');
        }
        if ($this->db->DBDriver === 'SQLite3') {
            $this->db->query('PRAGMA legacy_alter_table = OFF');
        }
    }
}
