<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table         = 'payments';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'tenant_id',
        'app_id',
        'subscription_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'provider',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'stripe_invoice_id',
        'receipt_url',
        'error_code',
        'error_message',
        'paid_at',
        'due_at',
    ];
}