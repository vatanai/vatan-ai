<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'array'];

    public static function display(): array
    {
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
        $fallback = [
            'active_template' => 'vatan-proposal',
            'title' => 'پلن‌ها، بر پایه اعتبار دائمی',
            'notes' => [
                'هر اعتبار همیشه در حساب شما می‌ماند؛ با کیفیت دلخواه خود می‌توانید بسازید.',
            ],
        ];

        try {
            $setting = static::query()->where('key', 'home_pricing')->first();
            $value = $setting?->value;

            return is_array($value) ? array_replace_recursive($fallback, $value) : $fallback;
        } catch (\Throwable $exception) {
            // تنظیمات قیمت‌گذاری نباید در نبود جدول یا عقب‌بودن migrationها
            // باعث خطای ۵۰۰ صفحه‌ی عمومی شود.
            report($exception);

            return $fallback;
        }
    }
}
