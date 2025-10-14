<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIncompleteExpiresToSubscriptions extends Migration
{
    public function up(): void
    {
        $fields = [
            'incomplete_expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'current_period_end',
                'comment' => 'Data/hora em que expira o estado pendente/incompleto/dunning',
            ],
        ];

        $this->forge->addColumn('subscriptions', $fields);
    }

    public function down(): void
    {
        $this->forge->dropColumn('subscriptions', 'incomplete_expires_at');
    }
}