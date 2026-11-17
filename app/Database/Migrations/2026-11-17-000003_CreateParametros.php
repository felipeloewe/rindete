<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateParametros extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('empresas', [
            'parametros_sembrados'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'monto_maximo_rendicion'  => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'detener_monto'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'detener_tipo'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'detener_habilitacion'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addColumn('gastos', [
            'no_aceptado' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'fuera_tipo'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ]);

        $this->tabla('cuentas', [
            'clase'      => ['type' => 'VARCHAR', 'constraint' => 20],
            'codigo'     => ['type' => 'VARCHAR', 'constraint' => 32],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 120],
            'concepto'   => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => ''],
            'usuario_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'activa'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->tabla('conexiones', [
            'nombre'  => ['type' => 'VARCHAR', 'constraint' => 120],
            'destino' => ['type' => 'VARCHAR', 'constraint' => 20],
            'vigente' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->tabla('aceptadores', [
            'orden'      => ['type' => 'INT', 'default' => 1],
            'usuario_id' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->tabla('tarjetas', [
            'nombre'          => ['type' => 'VARCHAR', 'constraint' => 120],
            'personal'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'monto_aceptado'  => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'activa'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->tabla('tipos_documento', [
            'nombre'           => ['type' => 'VARCHAR', 'constraint' => 80],
            'monto_aceptado'   => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'sin_comprobante'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'activo'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->tabla('pre_asientos', [
            'gasto_id'  => ['type' => 'INT', 'unsigned' => true],
            'cuenta_id' => ['type' => 'INT', 'unsigned' => true],
            'monto'     => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'moneda'    => ['type' => 'CHAR', 'constraint' => 3],
            'estado'    => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'propuesto'],
            'creado'    => ['type' => 'DATETIME'],
        ]);

        $this->pivot('categoria_cuentas', ['categoria_id', 'cuenta_id']);
        $this->pivot('gastos_aceptados', ['usuario_id', 'categoria_id']);
        $this->pivot('tarjeta_usuarios', ['tarjeta_id', 'usuario_id']);
        $this->pivot('tarjeta_categorias', ['tarjeta_id', 'categoria_id']);
    }

    public function down(): void
    {
        foreach (['tarjeta_categorias', 'tarjeta_usuarios', 'gastos_aceptados', 'categoria_cuentas', 'pre_asientos', 'tipos_documento', 'tarjetas', 'aceptadores', 'conexiones', 'cuentas'] as $tabla) {
            $this->forge->dropTable($tabla, true);
        }
        $this->forge->dropColumn('gastos', ['no_aceptado', 'fuera_tipo']);
        $this->forge->dropColumn('empresas', ['parametros_sembrados', 'monto_maximo_rendicion', 'detener_monto', 'detener_tipo', 'detener_habilitacion']);
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

    /**
     * @param list<string> $columnas
     */
    private function pivot(string $nombre, array $columnas): void
    {
        $campos = ['id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true]];
        foreach ($columnas as $columna) {
            $campos[$columna] = ['type' => 'INT', 'unsigned' => true];
        }
        $this->forge->addField($campos);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey($columnas);
        $this->forge->createTable($nombre);
    }
}
