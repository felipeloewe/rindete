<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGastos extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('empresas', [
            'detectar_duplicados' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'dias_duplicado'      => ['type' => 'INT', 'default' => 3],
            'comprobante_desde'   => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 1],
        ]);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'empresa_id' => ['type' => 'INT', 'unsigned' => true],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 80],
            'detalle'    => ['type' => 'VARCHAR', 'constraint' => 180, 'default' => ''],
            'tope'       => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'activa'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('empresa_id');
        $this->forge->addForeignKey('empresa_id', 'empresas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('categorias');

        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'empresa_id'         => ['type' => 'INT', 'unsigned' => true],
            'usuario_id'         => ['type' => 'INT', 'unsigned' => true],
            'categoria_id'       => ['type' => 'INT', 'unsigned' => true],
            'concepto'           => ['type' => 'VARCHAR', 'constraint' => 140],
            'proveedor'          => ['type' => 'VARCHAR', 'constraint' => 140],
            'fecha'              => ['type' => 'DATE'],
            'monto'              => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'moneda'             => ['type' => 'CHAR', 'constraint' => 3],
            'tipo_doc'           => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => ''],
            'id_fiscal'          => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => ''],
            'num_doc'            => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => ''],
            'codigo_fiscal'      => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => ''],
            'nota'               => ['type' => 'VARCHAR', 'constraint' => 500, 'default' => ''],
            'comprobante'        => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
            'estado'             => ['type' => 'VARCHAR', 'constraint' => 32],
            'duplicado'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'fuera_politica'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sin_comprobante'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'fiscal_incompleto'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'creado'             => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['empresa_id', 'fecha']);
        $this->forge->addKey('usuario_id');
        $this->forge->addKey('categoria_id');
        $this->forge->addForeignKey('empresa_id', 'empresas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('categoria_id', 'categorias', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('gastos');
    }

    public function down(): void
    {
        $this->forge->dropTable('gastos');
        $this->forge->dropTable('categorias');
        $this->forge->dropColumn('empresas', ['detectar_duplicados', 'dias_duplicado', 'comprobante_desde']);
    }
}
