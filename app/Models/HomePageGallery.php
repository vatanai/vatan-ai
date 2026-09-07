<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HomePageGallery extends Model
{
    protected $fillable = [
        'key',
        'title',
        'description',
        'items',
        'is_active',
        'position',
    ];

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }
}
