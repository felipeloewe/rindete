<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SaldosRendicion extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('rendiciones', [
            'fondo_id'            => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'origen'],
            'rendicion_previa_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'fondo_id'],
            'monto_por_rendir'    => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0, 'after' => 'rendicion_previa_id'],
            'monto_adicional'     => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0, 'after' => 'monto_por_rendir'],
            'turno_usuario_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'monto_adicional'],
            'turno_orden'         => ['type' => 'INT', 'default' => 0, 'after' => 'turno_usuario_id'],
        ]);

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'rendicion_id' => ['type' => 'INT', 'unsigned' => true],
            'usuario_id'   => ['type' => 'INT', 'unsigned' => true],
            'orden'        => ['type' => 'INT', 'default' => 1],
            'decision'     => ['type' => 'VARCHAR', 'constraint' => 20],
            'detalle'      => ['type' => 'VARCHAR', 'constraint' => 180, 'default' => ''],
            'creada'       => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('rendicion_id');
        $this->forge->addForeignKey('rendicion_id', 'rendiciones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('rendicion_pasos');
    }

    public function down(): void
    {
        $this->forge->dropTable('rendicion_pasos', true);
        $this->forge->dropColumn('rendiciones', [
            'fondo_id', 'rendicion_previa_id', 'monto_por_rendir', 'monto_adicional', 'turno_usuario_id', 'turno_orden',
        ]);
    }
}
