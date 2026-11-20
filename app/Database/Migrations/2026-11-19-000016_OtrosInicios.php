<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class OtrosInicios extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('fondos', [
            'origen' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '', 'after' => 'estado'],
        ]);

        $this->tabla('formas_pago', [
            'nombre'   => ['type' => 'VARCHAR', 'constraint' => 80],
            'efectivo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'activo'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->tabla('documentos', [
            'usuario_id'    => ['type' => 'INT', 'unsigned' => true],
            'forma_pago_id' => ['type' => 'INT', 'unsigned' => true],
            'motivo'        => ['type' => 'VARCHAR', 'constraint' => 180],
            'monto'         => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'moneda'        => ['type' => 'CHAR', 'constraint' => 3],
            'comprobante'   => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => ''],
            'estado'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'informado'],
            'creada'        => ['type' => 'DATETIME'],
            'resuelta'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->tabla('cargos_tarjeta', [
            'tarjeta_id'   => ['type' => 'INT', 'unsigned' => true],
            'usuario_id'   => ['type' => 'INT', 'unsigned' => true],
            'categoria_id' => ['type' => 'INT', 'unsigned' => true],
            'motivo'       => ['type' => 'VARCHAR', 'constraint' => 180],
            'monto'        => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'moneda'       => ['type' => 'CHAR', 'constraint' => 3],
            'dte'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'comprobante'  => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => ''],
            'estado'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'en_validacion'],
            'detalle'      => ['type' => 'VARCHAR', 'constraint' => 180, 'default' => ''],
            'fondo_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'rendicion_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'creada'       => ['type' => 'DATETIME'],
        ]);
        $this->tabla('caja_movimientos', [
            'usuario_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'tipo'         => ['type' => 'VARCHAR', 'constraint' => 20],
            'motivo'       => ['type' => 'VARCHAR', 'constraint' => 180],
            'monto'        => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'moneda'       => ['type' => 'CHAR', 'constraint' => 3],
            'fondo_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'rendicion_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'gasto_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'duplicado'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'creada'       => ['type' => 'DATETIME'],
        ]);
        $this->tabla('entidades', [
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 120],
            'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->tabla('pagos_socio', [
            'usuario_id' => ['type' => 'INT', 'unsigned' => true],
            'entidad_id' => ['type' => 'INT', 'unsigned' => true],
            'nota'       => ['type' => 'VARCHAR', 'constraint' => 180, 'default' => ''],
            'estado'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'por_recuperar'],
            'creada'     => ['type' => 'DATETIME'],
        ]);
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'pago_id'       => ['type' => 'INT', 'unsigned' => true],
            'forma_pago_id' => ['type' => 'INT', 'unsigned' => true],
            'motivo'        => ['type' => 'VARCHAR', 'constraint' => 180],
            'monto'         => ['type' => 'DECIMAL', 'constraint' => '14,2'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('pago_id');
        $this->forge->addForeignKey('pago_id', 'pagos_socio', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pago_socio_lineas');
    }

    public function down(): void
    {
        $this->forge->dropTable('pago_socio_lineas', true);
        foreach (['pagos_socio', 'entidades', 'caja_movimientos', 'cargos_tarjeta', 'documentos', 'formas_pago'] as $tabla) {
            $this->forge->dropTable($tabla, true);
        }
        $this->forge->dropColumn('fondos', 'origen');
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
