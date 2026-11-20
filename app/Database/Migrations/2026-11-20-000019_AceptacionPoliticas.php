<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AceptacionPoliticas extends Migration
{
    public function up(): void
    {
        $this->agregar('usuarios');
        $this->agregar('empresas');
    }

    public function down(): void
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $this->db->query('PRAGMA legacy_alter_table = ON');
        }
        foreach (['usuarios', 'empresas'] as $tabla) {
            foreach (['politica_version', 'politica_aceptada_en'] as $columna) {
                if ($this->db->fieldExists($columna, $tabla)) {
                    $this->forge->dropColumn($tabla, $columna);
                }
            }
        }
        if ($this->db->DBDriver === 'SQLite3') {
            $this->db->query('PRAGMA legacy_alter_table = OFF');
        }
    }

    private function agregar(string $tabla): void
    {
        if (! $this->db->fieldExists('politica_version', $tabla)) {
            $this->forge->addColumn($tabla, [
                'politica_version' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => ''],
            ]);
        }
        if (! $this->db->fieldExists('politica_aceptada_en', $tabla)) {
            $this->forge->addColumn($tabla, [
                'politica_aceptada_en' => ['type' => 'DATETIME', 'null' => true],
            ]);
        }
    }
}
