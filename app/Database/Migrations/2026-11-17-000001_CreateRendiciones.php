<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRendiciones extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'empresa_id' => ['type' => 'INT', 'unsigned' => true],
            'usuario_id' => ['type' => 'INT', 'unsigned' => true],
            'titulo'     => ['type' => 'VARCHAR', 'constraint' => 140],
            'nota'       => ['type' => 'VARCHAR', 'constraint' => 500, 'default' => ''],
            'estado'     => ['type' => 'VARCHAR', 'constraint' => 32],
            'creada'     => ['type' => 'DATETIME'],
            'enviada'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['empresa_id', 'estado']);
        $this->forge->addKey('usuario_id');
        $this->forge->addForeignKey('empresa_id', 'empresas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('rendiciones');

        $this->forge->addColumn('gastos', [
            'rendicion_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'usuario_id'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('gastos', 'rendicion_id');
        $this->forge->dropTable('rendiciones');
    }
}
