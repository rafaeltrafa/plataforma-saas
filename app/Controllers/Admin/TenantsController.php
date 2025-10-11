<?php

namespace App\Controllers\Admin;

class TenantsController extends BaseAdminController
{
    public function index()
    {
        // Placeholder de dados; depois integra com Model real
        $data = [
            'tenants' => [
                ['id' => 1, 'name' => 'Tenant Alpha'],
                ['id' => 2, 'name' => 'Tenant Beta'],
            ],
        ];

        return view('admin/tenants/index', $data);
    }
}