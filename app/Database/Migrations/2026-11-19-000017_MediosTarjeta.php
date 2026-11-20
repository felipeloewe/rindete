<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MediosTarjeta extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('tarjetas', [
            'ultimos4'   => ['type' => 'VARCHAR', 'constraint' => 4, 'default' => '', 'after' => 'nombre'],
            'emisor'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '', 'after' => 'ultimos4'],
            'referencia' => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => '', 'after' => 'emisor'],
            'billetera'  => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => '', 'after' => 'referencia'],
            'titular_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'billetera'],
            'exclusiva'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'titular_id'],
        ]);

        $this->tabla('medios_conexion', [
            'proveedor'      => ['type' => 'VARCHAR', 'constraint' => 20],
            'clave'          => ['type' => 'VARCHAR', 'constraint' => 64],
            'secreto'        => ['type' => 'TEXT'],
            'token_consulta' => ['type' => 'TEXT', 'null' => true],
            'vigente'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'empresa_id'  => ['type' => 'INT', 'unsigned' => true],
            'proveedor'   => ['type' => 'VARCHAR', 'constraint' => 20],
            'externo_id'  => ['type' => 'VARCHAR', 'constraint' => 80],
            'tarjeta_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'usuario_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'sentido'     => ['type' => 'VARCHAR', 'constraint' => 10],
            'monto'       => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'moneda'      => ['type' => 'CHAR', 'constraint' => 3],
            'detalle'     => ['type' => 'VARCHAR', 'constraint' => 180, 'default' => ''],
            'estado'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'trazado'],
            'cargo_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'ocurrido'    => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('empresa_id');
        $this->forge->addUniqueKey(['empresa_id', 'proveedor', 'externo_id']);
        $this->forge->addForeignKey('empresa_id', 'empresas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('movimientos_medio');
    }

    public function down(): void
    {
        $this->forge->dropTable('movimientos_medio', true);
        $this->forge->dropTable('medios_conexion', true);
        $this->forge->dropColumn('tarjetas', ['ultimos4', 'emisor', 'referencia', 'billetera', 'titular_id', 'exclusiva']);
    }

    /**
     * @param array<string, array<string, mixed>> $campos
     */
    private function tabla(string $nombre, array $campos): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'empresa_id' => ['type' => 'INT', 'unsigned' => true],
        ] + $campos);
        $this->forge->addKey('id', true);
        $this->forge->addKey('empresa_id');
        $this->forge->addForeignKey('empresa_id', 'empresas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable($nombre);
    }
}
