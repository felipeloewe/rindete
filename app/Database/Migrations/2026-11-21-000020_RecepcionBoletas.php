<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RecepcionBoletas extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('entradas')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'empresa_id'  => ['type' => 'INT', 'unsigned' => true],
                'usuario_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'canal'       => ['type' => 'VARCHAR', 'constraint' => 12],
                'emisor'      => ['type' => 'VARCHAR', 'constraint' => 160, 'default' => ''],
                'receptor'    => ['type' => 'VARCHAR', 'constraint' => 160, 'default' => ''],
                'mensaje_id'  => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
                'texto'       => ['type' => 'TEXT', 'null' => true],
                'comprobante' => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => ''],
                'estado'      => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'pendiente'],
                'creado'      => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey('mensaje_id');
            $this->forge->addKey(['empresa_id', 'estado']);
            $this->forge->createTable('entradas');
        }

        $db    = \Config\Database::connect('identidad');
        $forge = \Config\Database::forge('identidad');
        if ($db->tableExists('personas') && ! $db->fieldExists('telefono', 'personas')) {
            $forge->addColumn('personas', [
                'telefono' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => ''],
            ]);
        }
        if ($db->tableExists('organizaciones') && ! $db->fieldExists('whatsapp', 'organizaciones')) {
            $forge->addColumn('organizaciones', [
                'whatsapp'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => ''],
                'correo_boletas' => ['type' => 'VARCHAR', 'constraint' => 160, 'default' => ''],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('entradas')) {
            $this->forge->dropTable('entradas');
        }
        $db    = \Config\Database::connect('identidad');
        $forge = \Config\Database::forge('identidad');
        if ($db->DBDriver === 'SQLite3') {
            $db->query('PRAGMA legacy_alter_table = ON');
        }
        if ($db->tableExists('personas') && $db->fieldExists('telefono', 'personas')) {
            $forge->dropColumn('personas', 'telefono');
        }
        if ($db->tableExists('organizaciones')) {
            foreach (['whatsapp', 'correo_boletas'] as $columna) {
                if ($db->fieldExists($columna, 'organizaciones')) {
                    $forge->dropColumn('organizaciones', $columna);
                }
            }
        }
        if ($db->DBDriver === 'SQLite3') {
            $db->query('PRAGMA legacy_alter_table = OFF');
        }
    }
}
