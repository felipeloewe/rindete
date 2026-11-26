<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Superadmin extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'usuario'      => ['type' => 'VARCHAR', 'constraint' => 40],
            'password'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'debe_cambiar' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'creado'       => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('usuario');
        $this->forge->createTable('maestros');
        $this->db->table('maestros')->insert([
            'usuario'      => 'Master',
            'password'     => password_hash('demo', PASSWORD_DEFAULT),
            'debe_cambiar' => 1,
            'creado'       => date('Y-m-d H:i:s'),
        ]);

        if ($this->db->tableExists('empresas')) {
            $columnas = [
                'cobro_plan'          => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => ''],
                'cobro_monto'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'cobro_moneda'        => ['type' => 'CHAR', 'constraint' => 3, 'default' => 'CLP'],
                'cobro_periodicidad'  => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => ''],
                'vigente'             => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            ];
            foreach ($columnas as $nombre => $campo) {
                if (! $this->db->fieldExists($nombre, 'empresas')) {
                    $this->forge->addColumn('empresas', [$nombre => $campo]);
                }
            }
        }
        if ($this->db->tableExists('usuarios') && ! $this->db->fieldExists('clave_inicial', 'usuarios')) {
            $this->forge->addColumn('usuarios', [
                'clave_inicial' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            ]);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('maestros', true);
        if ($this->db->DBDriver === 'SQLite3') {
            $this->db->query('PRAGMA legacy_alter_table = ON');
        }
        foreach (['vigente', 'cobro_periodicidad', 'cobro_moneda', 'cobro_monto', 'cobro_plan'] as $columna) {
            if ($this->db->tableExists('empresas') && $this->db->fieldExists($columna, 'empresas')) {
                $this->forge->dropColumn('empresas', $columna);
            }
        }
        if ($this->db->tableExists('usuarios') && $this->db->fieldExists('clave_inicial', 'usuarios')) {
            $this->forge->dropColumn('usuarios', 'clave_inicial');
        }
        if ($this->db->DBDriver === 'SQLite3') {
            $this->db->query('PRAGMA legacy_alter_table = OFF');
        }
    }
}
