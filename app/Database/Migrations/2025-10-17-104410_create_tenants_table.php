<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantsTable extends Migration
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
                'constraint' => 150,
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
            ],
            'contact_email' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
            ],
            'contact_phone' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'document_number' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'subdomain' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
            ],
            'domain' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
            ],
            'locale' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => 'pt_BR',
            ],
            'timezone' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'America/Sao_Paulo',
            ],
            'password_hash' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'last_login_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'stripe_customer_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'trial_end_at' => [
                'type' => 'DATETIME',
                'null' => true,
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
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('slug', false, true);
        $this->forge->addKey('subdomain', false, true);
        $this->forge->addKey('domain', false, true);
        $this->forge->addKey('stripe_customer_id');
        $this->forge->addKey('is_active');

        // IF NOT EXISTS
        $this->forge->createTable('tenants', true);
    }

    public function down()
    {
        // IF EXISTS
        $this->forge->dropTable('tenants', true);
    }
}