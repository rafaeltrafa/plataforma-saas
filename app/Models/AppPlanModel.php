<?php

namespace App\Models;

use CodeIgniter\Model;

class AppPlanModel extends Model
{
    protected $table            = 'app_plans';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $protectFields    = true;
    protected $allowedFields    = [
        'app_id',
        'name',
        'slug',
        'plan_key',
        'billing_interval', // mensal, anual, etc.
        'price_amount',
        'currency',
        'stripe_price_id',
        'status',       // ativo, inativo, em_revisao, etc.
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}