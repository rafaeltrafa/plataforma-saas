<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLeadsTable extends Migration
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
            'app_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
            ],
            'whatsapp' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('app_id');
        $this->forge->addKey('email');
        // Evitar duplicidade de lead por app
        $this->forge->addKey(['email', 'app_id'], false, true);

        $this->forge->addForeignKey('app_id', 'apps', 'id', 'SET NULL', 'CASCADE');

        // IF NOT EXISTS
        $this->forge->createTable('leads', true);
    }

    public function down()
    {
        // IF EXISTS
        $this->forge->dropTable('leads', true);
    }
}