<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRoleLevelsTable extends Migration
{
    protected $group = 'default';
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'level' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'is_default' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('level', false, true);
        $this->forge->addKey('is_active');

        // IF NOT EXISTS
        $this->forge->createTable('role_levels', true);
    }

    public function down()
    {
        // IF EXISTS
        $this->forge->dropTable('role_levels', true);
    }
}