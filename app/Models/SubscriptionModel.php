<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionModel extends Model
{
    protected $table            = 'subscriptions';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = [
        'tenant_id',
        'app_id',
        'app_plan_id',
        'stripe_subscription_id',
        'status',
        'is_active',
        'unit_price',
        'currency',
        'period_start',
        'period_end',
        'current_period_start',
        'current_period_end',
        'cancel_at_period_end',
        'canceled_at',
        'trial_end_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    // Convenience: base select for list view
    public function selectForList()
    {
        return $this
            ->select([
                'subscriptions.id as subscription_id',
                'subscriptions.tenant_id',
                'subscriptions.app_id',
                'subscriptions.app_plan_id',
                'subscriptions.status',
                'subscriptions.unit_price',
                'subscriptions.currency',
                'tenants.name as tenant_name',
                'tenants.contact_email as tenant_email',
                'tenants.stripe_customer_id as stripe_customer_id',
                'apps.name as app_name',
                'app_plans.name as plan_name',
                'app_plans.price_amount as plan_price_amount',
                'app_plans.currency as plan_currency',
            ])
            ->join('tenants', 'tenants.id = subscriptions.tenant_id')
            ->join('apps', 'apps.id = subscriptions.app_id', 'left')
            ->join('app_plans', 'app_plans.id = subscriptions.app_plan_id', 'left');
    }
}