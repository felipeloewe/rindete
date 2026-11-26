<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RendidorMarcado extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('usuarios') && ! $this->db->fieldExists('rinde', 'usuarios')) {
            $this->forge->addColumn('usuarios', [
                'rinde' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            ]);
        }
        if (! $this->db->tableExists('usuarios')) {
            return;
        }
        $this->db->table('usuarios')->where('rol', 'rendidor')->update(['rinde' => 1]);
        $ids = [];
        foreach (['gastos', 'rendiciones'] as $tabla) {
            if (! $this->db->tableExists($tabla)) {
                continue;
            }
            foreach ($this->db->table($tabla)->select('usuario_id')->distinct()->get()->getResultArray() as $fila) {
                $ids[] = (int) ($fila['usuario_id'] ?? 0);
            }
        }
        $ids = array_values(array_filter(array_unique($ids)));
        if ($ids !== []) {
            $this->db->table('usuarios')->whereIn('id', $ids)->update(['rinde' => 1]);
        }
    }

    public function down(): void
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $this->db->query('PRAGMA legacy_alter_table = ON');
        }
        if ($this->db->tableExists('usuarios') && $this->db->fieldExists('rinde', 'usuarios')) {
            $this->forge->dropColumn('usuarios', 'rinde');
        }
        if ($this->db->DBDriver === 'SQLite3') {
            $this->db->query('PRAGMA legacy_alter_table = OFF');
        }
    }
}
