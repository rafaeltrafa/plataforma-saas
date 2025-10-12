<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantModel extends Model
{
    protected $table          = 'tenants';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields  = [
        'name',
        'slug',
        'contact_email',
        'contact_phone',
        'document_number',
        'subdomain',
        'domain',
        'locale',
        'timezone',
        'stripe_customer_id',
        'trial_end_at',
        'is_active',
        'password_hash',
        'last_login_at',
    ];
}