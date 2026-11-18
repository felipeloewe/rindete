<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NominaEnElBanco extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('usuarios', [
            'cuenta_banco' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => '', 'after' => 'id_fiscal'],
        ]);
        $this->forge->addColumn('conexiones', [
            'url'       => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => '', 'after' => 'sistema'],
            'token_api' => ['type' => 'TEXT', 'null' => true, 'after' => 'token_huella'],
        ]);
        $this->forge->addColumn('nomina_lineas', [
            'cuenta_banco' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => '', 'after' => 'rut'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('nomina_lineas', 'cuenta_banco');
        $this->forge->dropColumn('conexiones', ['url', 'token_api']);
        $this->forge->dropColumn('usuarios', 'cuenta_banco');
    }
}
