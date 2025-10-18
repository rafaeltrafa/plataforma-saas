<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ResetMigrations extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'reset:migrations';
    protected $description = 'Drop the migrations table to allow a full re-run of all migrations from scratch.';
    protected $usage = 'php spark reset:migrations';

    public function run(array $params)
    {
        $db = db_connect();
        try {
            $db->query('DROP TABLE IF EXISTS migrations');
            CLI::write('Migrations table dropped successfully.', 'green');
        } catch (\Throwable $e) {
            CLI::error('Failed to drop migrations table: ' . $e->getMessage());
            return;
        }
    }
}