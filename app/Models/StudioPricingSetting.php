<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudioPricingSetting extends Model
{
    protected $fillable = ['image_profit_percent', 'video_profit_percent'];

    protected function casts(): array
    {
        return [
            'image_profit_percent' => 'decimal:2',
            'video_profit_percent' => 'decimal:2',
        ];
    }
}
