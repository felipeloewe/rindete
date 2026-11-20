<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CapaIdentidades extends Migration
{
    public function up(): void
    {
        $forge = \Config\Database::forge('identidad');
        $db    = \Config\Database::connect('identidad');

        $forge->addField([
            'usuario_id'    => ['type' => 'INT', 'unsigned' => true],
            'empresa_id'    => ['type' => 'INT', 'unsigned' => true],
            'nombre'        => ['type' => 'VARCHAR', 'constraint' => 80],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 160],
            'id_fiscal'     => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'cuenta_banco'  => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => ''],
            'banco_destino' => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => ''],
            'password'      => ['type' => 'VARCHAR', 'constraint' => 255],
        ]);
        $forge->addPrimaryKey('usuario_id');
        $forge->addUniqueKey('email');
        $forge->createTable('personas');

        $forge->addField([
            'empresa_id'   => ['type' => 'INT', 'unsigned' => true],
            'pais'         => ['type' => 'CHAR', 'constraint' => 2],
            'nombre'       => ['type' => 'VARCHAR', 'constraint' => 120],
            'id_fiscal'    => ['type' => 'VARCHAR', 'constraint' => 40],
            'cuenta_cargo' => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => ''],
        ]);
        $forge->addPrimaryKey('empresa_id');
        $forge->addUniqueKey(['pais', 'id_fiscal']);
        $forge->createTable('organizaciones');

        $this->alteracionAntigua(true);
        if ($this->db->fieldExists('email', 'usuarios')) {
            foreach ($this->db->table('usuarios')->get()->getResultArray() as $fila) {
                $db->table('personas')->insert([
                    'usuario_id'    => (int) $fila['id'],
                    'empresa_id'    => (int) $fila['empresa_id'],
                    'nombre'        => (string) $fila['nombre'],
                    'email'         => strtolower(trim((string) $fila['email'])),
                    'id_fiscal'     => ($fila['id_fiscal'] ?? '') === '' ? null : $fila['id_fiscal'],
                    'cuenta_banco'  => (string) ($fila['cuenta_banco'] ?? ''),
                    'banco_destino' => (string) ($fila['banco_destino'] ?? ''),
                    'password'      => (string) $fila['password'],
                ]);
            }
            foreach (['nombre', 'email', 'id_fiscal', 'cuenta_banco', 'banco_destino', 'password'] as $columna) {
                if ($this->db->fieldExists($columna, 'usuarios')) {
                    $this->forge->dropColumn('usuarios', $columna);
                }
            }
        }

        if ($this->db->fieldExists('id_fiscal', 'empresas')) {
            foreach ($this->db->table('empresas')->get()->getResultArray() as $fila) {
                $db->table('organizaciones')->insert([
                    'empresa_id'   => (int) $fila['id'],
                    'pais'         => (string) $fila['pais'],
                    'nombre'       => (string) $fila['nombre'],
                    'id_fiscal'    => (string) $fila['id_fiscal'],
                    'cuenta_cargo' => (string) ($fila['cuenta_cargo'] ?? ''),
                ]);
            }
            foreach (['nombre', 'id_fiscal', 'cuenta_cargo'] as $columna) {
                if ($this->db->fieldExists($columna, 'empresas')) {
                    $this->forge->dropColumn('empresas', $columna);
                }
            }
        }
        $this->alteracionAntigua(false);
    }

    public function down(): void
    {
        $forge = \Config\Database::forge('identidad');
        $db    = \Config\Database::connect('identidad');

        $this->alteracionAntigua(true);
        if (! $this->db->fieldExists('email', 'usuarios') && $db->tableExists('personas')) {
            $this->forge->addColumn('usuarios', [
                'nombre'        => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => ''],
                'email'         => ['type' => 'VARCHAR', 'constraint' => 160, 'default' => ''],
                'password'      => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
                'id_fiscal'     => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
                'cuenta_banco'  => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => ''],
                'banco_destino' => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => ''],
            ]);
            foreach ($db->table('personas')->get()->getResultArray() as $fila) {
                $this->db->table('usuarios')->where('id', $fila['usuario_id'])->update([
                    'nombre'        => $fila['nombre'],
                    'email'         => $fila['email'],
                    'password'      => $fila['password'],
                    'id_fiscal'     => $fila['id_fiscal'],
                    'cuenta_banco'  => $fila['cuenta_banco'],
                    'banco_destino' => $fila['banco_destino'],
                ]);
            }
        }

        if (! $this->db->fieldExists('id_fiscal', 'empresas') && $db->tableExists('organizaciones')) {
            $this->forge->addColumn('empresas', [
                'nombre'       => ['type' => 'VARCHAR', 'constraint' => 120, 'default' => ''],
                'id_fiscal'    => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => ''],
                'cuenta_cargo' => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => ''],
            ]);
            foreach ($db->table('organizaciones')->get()->getResultArray() as $fila) {
                $this->db->table('empresas')->where('id', $fila['empresa_id'])->update([
                    'nombre'       => $fila['nombre'],
                    'id_fiscal'    => $fila['id_fiscal'],
                    'cuenta_cargo' => $fila['cuenta_cargo'],
                ]);
            }
        }

        $forge->dropTable('personas', true);
        $forge->dropTable('organizaciones', true);
        $this->alteracionAntigua(false);
    }

    private function alteracionAntigua(bool $activa): void
    {
        if ($this->db->DBDriver !== 'SQLite3') {
            return;
        }
        $this->db->query('PRAGMA legacy_alter_table = ' . ($activa ? 'ON' : 'OFF'));
    }
}
