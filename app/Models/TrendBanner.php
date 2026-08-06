<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrendBanner extends Model
{
    protected $fillable = [
        'title',
        'image_desktop',
        'image_mobile',
        'display_target',
        'row_number',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'row_number' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function imageUrl(string $device = 'desktop'): string
    {
        $path = $device === 'mobile' ? $this->image_mobile : $this->image_desktop;

        return asset('storage/' . ltrim((string) $path, '/'));
    }
}
