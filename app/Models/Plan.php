<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_code',
        'name',
        'slug',
        'price',
        'tokens',
        'image_path'
    ];

    protected $casts = [
        'price' => 'integer',
        'tokens' => 'integer',
    ];

    /**
     * متد هوشمند برای ساخت خودکار کد ۶ تا ۷ کاراکتری بر اساس اسلاگ دستی ادمین
     */
    protected static function booted()
    {
        static::creating(function ($plan) {
            if (empty($plan->plan_code)) {
                $englishSlug = Str::slug($plan->slug ?? $plan->name, '-');
                $cleanString = preg_replace('/[^A-Za-z0-9\-]/', '', $englishSlug);
                $parts = array_filter(explode('-', $cleanString));
                
                $shortPrefix = '';
                if (count($parts) >= 2) {
                    foreach ($parts as $part) {
                        $shortPrefix .= substr($part, 0, 2);
                    }
                } else {
                    $shortPrefix = substr(reset($parts) ?: 'GEN', 0, 4);
                }

                $shortPrefix = strtoupper(substr($shortPrefix, 0, 4));
                
                do {
                    $randomSuffix = strtoupper(Str::random(3));
                    $finalCode = 'PLN-' . $shortPrefix . $randomSuffix;
                } while (static::where('plan_code', $finalCode)->exists());

                $plan->plan_code = $finalCode;
            }
        });
    }
}