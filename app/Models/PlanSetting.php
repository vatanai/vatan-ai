<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class PlanSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'array'];

    public static function display(): array
    {
        if (! Schema::hasTable('plan_settings')) {
            return static::displayFallback();
        }

        return static::query()->where('key', 'display')->value('value') ?? [
            'mode' => 'cards',
            'home_limit' => 4,
            'show_images' => false,
            'show_comparison' => true,
            'title' => 'پلن مناسب خودت را انتخاب کن',
            'subtitle' => 'از شروع رایگان تا راهکارهای سازمانی، متناسب با میزان استفاده شما',
        ];
    }

    public static function homePricing(): array
    {
        if (! Schema::hasTable('plan_settings')) {
            return static::homePricingFallback();
        }

        return static::query()->where('key', 'home_pricing')->value('value') ?? [
            'active_template' => 'vatan-proposal',
            'title' => 'پلن‌ها، بر پایه اعتبار دائمی',
            'notes' => [
                'هر اعتبار همیشه در حساب شما می‌ماند؛ با کیفیت دلخواه خود می‌توانید بسازید.',
                'کیفیت استاندارد، حرفه‌ای و بهترین خروجی به‌ترتیب ۱۲، ۲۰ و ۵۰ اعتبار برای هر ساخت نیاز دارند.',
            ],
        ];
    }

    private static function displayFallback(): array
    {
        return [
            'mode' => 'cards',
            'home_limit' => 4,
            'show_images' => false,
            'show_comparison' => true,
            'title' => 'پلن مناسب خودت را انتخاب کن',
            'subtitle' => 'از شروع رایگان تا راهکارهای سازمانی، متناسب با میزان استفاده شما',
        ];
    }

    private static function homePricingFallback(): array
    {
        return [
            'active_template' => 'vatan-proposal',
            'title' => 'پلن‌ها، بر پایه اعتبار دائمی',
            'notes' => [
                'هر اعتبار همیشه در حساب شما می‌ماند؛ با کیفیت دلخواه خود می‌توانید بسازید.',
                'کیفیت استاندارد، حرفه‌ای و بهترین خروجی به‌ترتیب ۱۲، ۲۰ و ۵۰ اعتبار برای هر ساخت نیاز دارند.',
            ],
        ];
    }
}
