<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class TraspasoLiquidacion extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('empresas', [
            'preasiento_liquidacion_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'preasiento_salida_id'],
        ]);
        $this->forge->addColumn('categorias', [
            'concepto' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '', 'after' => 'nombre'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('categorias', 'concepto');
        $this->forge->dropColumn('empresas', 'preasiento_liquidacion_id');
    }
}
