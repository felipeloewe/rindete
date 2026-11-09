<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmpresasUsuarios extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 120],
            'pais' => ['type' => 'CHAR', 'constraint' => 2],
            'id_fiscal' => ['type' => 'VARCHAR', 'constraint' => 40],
            'creado' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['pais', 'id_fiscal']);
        $this->forge->createTable('empresas');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'empresa_id' => ['type' => 'INT', 'unsigned' => true],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 80],
            'email' => ['type' => 'VARCHAR', 'constraint' => 160],
            'password' => ['type' => 'VARCHAR', 'constraint' => 255],
            'rol' => ['type' => 'VARCHAR', 'constraint' => 32],
            'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'creado' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('empresa_id');
        $this->forge->addForeignKey('empresa_id', 'empresas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('usuarios');
    }

    public function down(): void
    {
        $this->forge->dropTable('usuarios');
        $this->forge->dropTable('empresas');
    }
}
