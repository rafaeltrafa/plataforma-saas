<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class BaseAdminController extends BaseController
{
    // Espaço para helpers e configurações comuns do Admin
    protected $helpers = ['url'];
}