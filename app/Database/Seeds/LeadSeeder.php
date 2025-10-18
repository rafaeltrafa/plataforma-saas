<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class LeadSeeder extends Seeder
{
    public function run()
    {
        $builder = $this->db->table('leads');

        // Limpa a tabela para evitar duplicidades em reexecuções
        $builder->truncate();

        // Coleta IDs dos apps para vincular os leads
        $appIds = array_map(
            fn ($row) => $row['id'],
            $this->db->table('apps')->select('id')->get()->getResultArray()
        );

        // Se não houver apps, mantém app_id como NULL
        if (empty($appIds)) {
            $appIds = [null];
        }

        $now = date('Y-m-d H:i:s');
        $names = [
            'Ana Silva', 'Bruno Souza', 'Carla Mendes', 'Diego Lima', 'Eduarda Alves',
            'Felipe Rocha', 'Giovana Martins', 'Henrique Castro', 'Isabela Oliveira', 'João Pereira'
        ];

        $leads = [];
        foreach ($names as $i => $name) {
            $index = $i + 1;
            $leads[] = [
                'app_id'    => $appIds[$i % count($appIds)],
                'name'      => $name,
                'email'     => sprintf('lead%02d@example.com', $index),
                'whatsapp'  => '5511990000' . str_pad((string)$index, 2, '0', STR_PAD_LEFT),
                'created_at'=> $now,
                'updated_at'=> $now,
            ];
        }

        $builder->insertBatch($leads);
    }
}