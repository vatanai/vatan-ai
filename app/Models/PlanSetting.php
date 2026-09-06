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
}
