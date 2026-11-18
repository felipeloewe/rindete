<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFondos extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'empresa_id'       => ['type' => 'INT', 'unsigned' => true],
            'usuario_id'       => ['type' => 'INT', 'unsigned' => true],
            'categoria_id'     => ['type' => 'INT', 'unsigned' => true],
            'motivo'           => ['type' => 'VARCHAR', 'constraint' => 180],
            'monto'            => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'moneda'           => ['type' => 'CHAR', 'constraint' => 3],
            'estado'           => ['type' => 'VARCHAR', 'constraint' => 32],
            'detalle'          => ['type' => 'VARCHAR', 'constraint' => 180, 'default' => ''],
            'turno_usuario_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'turno_orden'      => ['type' => 'INT', 'default' => 0],
            'creada'           => ['type' => 'DATETIME'],
            'resuelta'         => ['type' => 'DATETIME', 'null' => true],
            'entregada'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['empresa_id', 'estado']);
        $this->forge->addKey('usuario_id');
        $this->forge->addForeignKey('empresa_id', 'empresas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('solicitudes');

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'solicitud_id'  => ['type' => 'INT', 'unsigned' => true],
            'usuario_id'    => ['type' => 'INT', 'unsigned' => true],
            'orden'         => ['type' => 'INT', 'default' => 1],
            'decision'      => ['type' => 'VARCHAR', 'constraint' => 20],
            'detalle'       => ['type' => 'VARCHAR', 'constraint' => 180, 'default' => ''],
            'creada'        => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('solicitud_id');
        $this->forge->addForeignKey('solicitud_id', 'solicitudes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('solicitud_pasos');

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'empresa_id'   => ['type' => 'INT', 'unsigned' => true],
            'usuario_id'   => ['type' => 'INT', 'unsigned' => true],
            'solicitud_id' => ['type' => 'INT', 'unsigned' => true],
            'motivo'       => ['type' => 'VARCHAR', 'constraint' => 180],
            'monto'        => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'saldo'        => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'moneda'       => ['type' => 'CHAR', 'constraint' => 3],
            'estado'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'abierto'],
            'creado'       => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('solicitud_id');
        $this->forge->addKey(['empresa_id', 'usuario_id']);
        $this->forge->addForeignKey('solicitud_id', 'solicitudes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('empresa_id', 'empresas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('fondos');
    }

    public function down(): void
    {
        $this->forge->dropTable('fondos', true);
        $this->forge->dropTable('solicitud_pasos', true);
        $this->forge->dropTable('solicitudes', true);
    }
}
