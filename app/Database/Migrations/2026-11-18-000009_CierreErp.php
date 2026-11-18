<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CierreErp extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('conexiones', [
            'sistema'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '', 'after' => 'nombre'],
            'token_huella' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true, 'after' => 'vigente'],
        ]);
        $this->forge->addColumn('preasiento_comprobantes', [
            'erp_ref'   => ['type' => 'VARCHAR', 'constraint' => 120, 'default' => '', 'after' => 'estado'],
            'consumido' => ['type' => 'DATETIME', 'null' => true, 'after' => 'erp_ref'],
        ]);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'empresa_id' => ['type' => 'INT', 'unsigned' => true],
            'anio'       => ['type' => 'INT'],
            'mes'        => ['type' => 'INT'],
            'estado'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'cerrada'],
            'erp_ref'    => ['type' => 'VARCHAR', 'constraint' => 120, 'default' => ''],
            'creada'     => ['type' => 'DATETIME'],
            'consumida'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['empresa_id', 'anio', 'mes']);
        $this->forge->addForeignKey('empresa_id', 'empresas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('nominas');

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nomina_id'    => ['type' => 'INT', 'unsigned' => true],
            'solicitud_id' => ['type' => 'INT', 'unsigned' => true],
            'usuario_id'   => ['type' => 'INT', 'unsigned' => true],
            'nombre'       => ['type' => 'VARCHAR', 'constraint' => 160],
            'rut'          => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => ''],
            'motivo'       => ['type' => 'VARCHAR', 'constraint' => 180],
            'monto'        => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'moneda'       => ['type' => 'CHAR', 'constraint' => 3],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('solicitud_id');
        $this->forge->addForeignKey('nomina_id', 'nominas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('solicitud_id', 'solicitudes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('nomina_lineas');
    }

    public function down(): void
    {
        $this->forge->dropTable('nomina_lineas', true);
        $this->forge->dropTable('nominas', true);
        $this->forge->dropColumn('preasiento_comprobantes', ['erp_ref', 'consumido']);
        $this->forge->dropColumn('conexiones', ['sistema', 'token_huella']);
    }
}
