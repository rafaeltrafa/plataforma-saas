<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenants extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
                'comment' => 'PK autoincrement do tenant',
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => false,
                'comment' => 'Nome legal/empresa do tenant',
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
                'comment' => 'Identificador legível único (URL-safe) do tenant',
            ],
            'contact_email' => [
                'type' => 'VARCHAR',
                'constraint' => 191,
                'null' => true,
                'comment' => 'E-mail de contato principal do tenant',
            ],
            'contact_phone' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
                'comment' => 'Telefone de contato principal do tenant',
            ],
            'document_number' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => true,
                'comment' => 'CPF/CNPJ ou equivalente',
            ],
            'subdomain' => [
                'type' => 'VARCHAR',
                'constraint' => 63,
                'null' => true,
                'comment' => 'Subdomínio próprio do tenant (ex.: acme)',
            ],
            'domain' => [
                'type' => 'VARCHAR',
                'constraint' => 191,
                'null' => true,
                'comment' => 'Domínio completo opcional do tenant (ex.: acme.com)',
            ],
            'locale' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => 'pt-BR',
                'comment' => 'Localidade padrão para o tenant (i18n)',
            ],
            'timezone' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'default' => 'America/Sao_Paulo',
                'comment' => 'Fuso horário padrão para o tenant',
            ],
            'stripe_customer_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'comment' => 'ID do cliente na Stripe',
            ],
            'trial_end_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Data/hora de término do período de teste',
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'comment' => 'Status de ativação do tenant (1=ativo,0=inativo)',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Criado em',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Atualizado em',
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Excluído em (soft delete)',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('is_active');
        $this->forge->addKey('stripe_customer_id');
        $this->forge->addKey('slug', false, true);
        $this->forge->addKey('subdomain', false, true);
        $this->forge->addKey('domain', false, true);

        $this->forge->createTable('tenants', false, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('tenants', true);
    }
}