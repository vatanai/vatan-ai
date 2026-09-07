<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelTierDefault extends Model
{
    public const KEYS = ['free', 'economy', 'pro', 'business'];

    protected $fillable = [
        'tier_key',
        'name',
        'description',
        'grade',
        'primary_model_id',
        'primary_provider',
        'fallback_model_id',
        'fallback_provider',
        'is_active',
    ];

    protected $casts = [
        'grade' => 'integer',
        'is_active' => 'boolean',
    ];
}
