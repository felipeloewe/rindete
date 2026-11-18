<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PersonasYRendicionDirecta extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('usuarios', [
            'puede_rendir_sin_solicitud' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'activo'],
            'monto_maximo_rendicion'     => ['type' => 'DECIMAL', 'constraint' => '14,2', 'null' => true, 'after' => 'puede_rendir_sin_solicitud'],
        ]);
        $this->db->table('usuarios')->where('rol', 'administrador')->update(['puede_rendir_sin_solicitud' => 1]);

        $this->forge->addColumn('rendiciones', [
            'origen'                => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'nota'],
            'monto_maximo'          => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0, 'after' => 'origen'],
            'cuenta_viatico_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'monto_maximo'],
            'cuenta_asignacion_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'cuenta_viatico_id'],
        ]);
        $this->db->table('rendiciones')->where('origen', null)->update(['origen' => 'anterior']);
    }

    public function down(): void
    {
        $this->forge->dropColumn('rendiciones', ['origen', 'monto_maximo', 'cuenta_viatico_id', 'cuenta_asignacion_id']);
        $this->forge->dropColumn('usuarios', ['puede_rendir_sin_solicitud', 'monto_maximo_rendicion']);
    }
}
