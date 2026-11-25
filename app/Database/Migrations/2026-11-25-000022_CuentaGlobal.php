<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CuentaGlobal extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('en_cuenta', 'fondos')) {
            $this->forge->addColumn('fondos', [
                'en_cuenta' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $this->db->query('PRAGMA legacy_alter_table = ON');
        }
        if ($this->db->fieldExists('en_cuenta', 'fondos')) {
            $this->forge->dropColumn('fondos', 'en_cuenta');
        }
        if ($this->db->DBDriver === 'SQLite3') {
            $this->db->query('PRAGMA legacy_alter_table = OFF');
        }
    }
}
