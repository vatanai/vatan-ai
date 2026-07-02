<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Providers\CrmServiceProvider::class, // CRM — مستقل از بقیه
];
