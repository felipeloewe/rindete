<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModulosContratados extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('empresas', [
            'preasiento_salida_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'preasiento_cuenta_id'],
        ]);

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'empresa_id'  => ['type' => 'INT', 'unsigned' => true],
            'codigo'      => ['type' => 'VARCHAR', 'constraint' => 40],
            'contratado'  => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['empresa_id', 'codigo']);
        $this->forge->addForeignKey('empresa_id', 'empresas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('empresa_modulos');
    }

    public function down(): void
    {
        $this->forge->dropTable('empresa_modulos', true);
        $this->forge->dropColumn('empresas', 'preasiento_salida_id');
    }
}
