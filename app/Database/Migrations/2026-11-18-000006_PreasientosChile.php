<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PreasientosChile extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('usuarios', [
            'id_fiscal' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'email'],
        ]);
        $this->forge->addColumn('empresas', [
            'preasiento_agrupacion' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'mes', 'after' => 'detener_habilitacion'],
            'preasiento_auxiliar'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'preasiento_agrupacion'],
            'preasiento_cuenta_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'preasiento_auxiliar'],
        ]);

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'empresa_id'  => ['type' => 'INT', 'unsigned' => true],
            'anio'        => ['type' => 'INT'],
            'mes'         => ['type' => 'INT'],
            'agrupacion'  => ['type' => 'VARCHAR', 'constraint' => 20],
            'usuario_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'tipo'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'traspaso'],
            'glosa'       => ['type' => 'VARCHAR', 'constraint' => 180],
            'fecha'       => ['type' => 'DATE'],
            'moneda'      => ['type' => 'CHAR', 'constraint' => 3],
            'estado'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'propuesto'],
            'creado'      => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['empresa_id', 'anio', 'mes']);
        $this->forge->addForeignKey('empresa_id', 'empresas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('preasiento_comprobantes');

        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'comprobante_id' => ['type' => 'INT', 'unsigned' => true],
            'orden'          => ['type' => 'INT', 'default' => 1],
            'cuenta_id'      => ['type' => 'INT', 'unsigned' => true],
            'glosa'          => ['type' => 'VARCHAR', 'constraint' => 180],
            'debe'           => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'haber'          => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'auxiliar'       => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => ''],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('comprobante_id');
        $this->forge->addForeignKey('comprobante_id', 'preasiento_comprobantes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('preasiento_lineas');
    }

    public function down(): void
    {
        $this->forge->dropTable('preasiento_lineas', true);
        $this->forge->dropTable('preasiento_comprobantes', true);
        $this->forge->dropColumn('empresas', ['preasiento_agrupacion', 'preasiento_auxiliar', 'preasiento_cuenta_id']);
        $this->forge->dropColumn('usuarios', 'id_fiscal');
    }
}
