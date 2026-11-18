<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ArchivoNominaBci extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('empresas', [
            'cuenta_cargo' => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => '', 'after' => 'id_fiscal'],
        ]);
        $this->forge->addColumn('usuarios', [
            'banco_destino' => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => '', 'after' => 'cuenta_banco'],
        ]);
        $this->forge->addColumn('nominas', [
            'cuenta_cargo' => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => '', 'after' => 'erp_ref'],
        ]);
        $this->forge->addColumn('nomina_lineas', [
            'banco_destino' => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => '', 'after' => 'cuenta_banco'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('nomina_lineas', 'banco_destino');
        $this->forge->dropColumn('nominas', 'cuenta_cargo');
        $this->forge->dropColumn('usuarios', 'banco_destino');
        $this->forge->dropColumn('empresas', 'cuenta_cargo');
    }
}
