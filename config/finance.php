<?php

return [
    'categories' => [
        'plan_sale' => 'فروش پلن',
        'other_income' => 'سایر درآمدها',
        'hosting' => 'هاست',
        'server' => 'سرور',
        'domain' => 'دامنه',
        'salary' => 'حقوق و دستمزد',
        'advertising' => 'تبلیغات',
        'tools' => 'ابزارها و اشتراک‌ها',
        'tax' => 'مالیات',
        'gateway_fee' => 'هزینه درگاه',
        'ai_model' => 'هزینه مدل هوش مصنوعی',
        'refund' => 'بازپرداخت',
        'investment' => 'سرمایه‌گذاری',
        'other_expense' => 'سایر هزینه‌ها',
    ],

    'statuses' => [
        'draft' => 'پیش‌نویس',
        'pending' => 'در انتظار پرداخت',
        'paid' => 'پرداخت‌شده',
        'overdue' => 'سررسید گذشته',
        'refunded' => 'بازپرداخت‌شده',
        'cancelled' => 'لغوشده',
    ],

    'sections' => [
        'overview' => 'نمای مالی',
        'cases' => 'پرونده خریدها',
        'transactions' => 'تراکنش‌ها',
        'expenses' => 'هزینه‌ها',
        'income' => 'درآمدها',
        'plans' => 'سود پلن‌ها',
        'products' => 'سود محصولات',
        'exchange-rates' => 'نرخ ارز',
        'cost-centers' => 'مراکز هزینه',
        'reports' => 'گزارش‌ها',
        'settings' => 'تنظیمات',
    ],

    'setting_defaults' => [
        'gateway_fee_percent' => 1,
        'infrastructure_allocation_percent' => 8,
        'workforce_allocation_percent' => 12,
        'estimated_model_cost_per_credit_toman' => 100,
        'negative_margin_alert_percent' => 0,
        'exchange_jump_alert_percent' => 5,
    ],
];
