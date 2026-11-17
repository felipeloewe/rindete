<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPlanEmpresa extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('empresas', [
            'plan' => [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'default'    => 'free',
                'after'      => 'id_fiscal',
            ],
            'cupo_usuarios' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'default'    => 4,
                'after'      => 'plan',
            ],
            'cupo_bytes' => [
                'type'       => 'BIGINT',
                'unsigned'   => true,
                'default'    => 2147483648,
                'after'      => 'cupo_usuarios',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('empresas', ['plan', 'cupo_usuarios', 'cupo_bytes']);
    }
}
