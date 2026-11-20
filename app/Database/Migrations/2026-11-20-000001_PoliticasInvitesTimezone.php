<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PoliticasInvitesTimezone extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('empresas', [
            'timezone' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'default'    => 'America/Santiago',
                'after'      => 'comprobante_desde',
            ],
        ]);

        $this->forge->addColumn('usuarios', [
            'invite_token' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'default'    => null,
                'after'      => 'activo',
            ],
            'invite_expires' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'after'   => 'invite_token',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('usuarios', ['invite_token', 'invite_expires']);
        $this->forge->dropColumn('empresas', 'timezone');
    }
}
