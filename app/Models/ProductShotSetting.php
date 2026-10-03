<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/** تنظیمات تک‌ردیفی «استودیو محصول» (فیچر فلگ، مخاطب، سقف‌ها، QC). */
class ProductShotSetting extends Model
{
    public const AUDIENCES = ['admins', 'whitelist', 'public'];

    protected $fillable = [
        'enabled', 'audience', 'whitelist_user_ids', 'whitelist_phones', 'max_shots_per_run',
        'client_concurrency', 'daily_cost_cap_usd', 'preflight_enabled', 'preflight_model',
        'qc_enabled', 'qc_model', 'qc_auto_retry', 'credit_price_toman',
        'preflight_prompt', 'preflight_blocking_issues', 'preflight_min_side',
        'product_sheet_enabled', 'product_sheet_size',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'whitelist_user_ids' => 'array',
        'whitelist_phones' => 'array',
        'max_shots_per_run' => 'integer',
        'client_concurrency' => 'integer',
        'daily_cost_cap_usd' => 'float',
        'preflight_enabled' => 'boolean',
        'qc_enabled' => 'boolean',
        'qc_auto_retry' => 'boolean',
        'credit_price_toman' => 'integer',
        'preflight_blocking_issues' => 'array',
        'preflight_min_side' => 'integer',
        'product_sheet_enabled' => 'boolean',
        'product_sheet_size' => 'integer',
    ];

    private static ?self $cached = null;

    public static function current(): self
    {
        if (self::$cached) {
            return self::$cached;
        }
        if (! Schema::hasTable('product_shot_settings')) {
            return self::$cached = new self(self::defaults());
        }

        return self::$cached = self::query()->first() ?? self::query()->create(self::defaults());
    }

    public static function forgetCache(): void
    {
        self::$cached = null;
    }

    public static function defaults(): array
    {
        return [
            'enabled' => false,
            'audience' => 'admins',
            'whitelist_user_ids' => [],
            'whitelist_phones' => [],
            'max_shots_per_run' => 6,
            'client_concurrency' => 1,
            'daily_cost_cap_usd' => 5,
            'preflight_enabled' => true,
            'preflight_prompt' => null,
            'preflight_blocking_issues' => ['no_product', 'blur', 'different_product'],
            'preflight_min_side' => 900,
            'qc_enabled' => true,
            'qc_auto_retry' => true,
            'credit_price_toman' => 585,
            'product_sheet_enabled' => true,
            'product_sheet_size' => 2048,
        ];
    }
}
