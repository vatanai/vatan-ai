<?php

use App\Providers\AppServiceProvider;

// موتور سئوی هوشمند — بارگذار مستقل (تا قبل از composer dump-autoload هم کار کند)
require_once __DIR__.'/../packages/vatan-seo/bootstrap.php';

return [
    AppServiceProvider::class,
    App\Providers\CrmServiceProvider::class, // CRM — مستقل از بقیه
    App\Providers\ExploreServiceProvider::class, // موتور فید (اکسپلور) — مستقل از بقیه
    App\Providers\HomeBuilderServiceProvider::class, // Home Builder — مستقل از بقیه
    App\Providers\SmartInstagramServiceProvider::class, // اینستاگرام هوشمند — مستقل از بقیه
    Vatan\Seo\SeoEngineServiceProvider::class, // موتور سئوی هوشمند — packages/vatan-seo (مستقل از بقیه)
];
