<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ContabilizadoManual extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('preasiento_comprobantes', [
            'origen' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => '', 'after' => 'consumido'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('preasiento_comprobantes', 'origen');
    }
}
