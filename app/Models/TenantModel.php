<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantModel extends Model
{
    protected $table            = 'tenants';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'slug',
        'contact_email',
        'contact_phone',
        'document_number',
        'subdomain',
        'domain',
        'locale',
        'timezone',
        'password_hash',
        'last_login_at',
        'stripe_customer_id',
        'trial_end_at',
        'is_active',
    ];

    // Datas
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}