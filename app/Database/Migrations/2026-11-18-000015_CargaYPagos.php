<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CargaYPagos extends Migration
{
    public function up(): void
    {
        foreach (['centros', 'sucursales', 'conceptos_extra'] as $tabla) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'empresa_id' => ['type' => 'INT', 'unsigned' => true],
                'nombre'     => ['type' => 'VARCHAR', 'constraint' => 80],
                'activo'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('empresa_id');
            $this->forge->addForeignKey('empresa_id', 'empresas', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable($tabla);
        }
        $this->forge->addColumn('gastos', [
            'centro_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'categoria_id'],
            'sucursal_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'centro_id'],
            'concepto_extra_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'sucursal_id'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('gastos', ['centro_id', 'sucursal_id', 'concepto_extra_id']);
        foreach (['conceptos_extra', 'sucursales', 'centros'] as $tabla) {
            $this->forge->dropTable($tabla, true);
        }
    }
}
