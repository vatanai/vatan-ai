<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'tokens',
        'image_path',
        'is_active'
    ];

    protected $casts = [
        'price' => 'integer',
        'tokens' => 'integer',
        'is_active' => 'boolean',
    ];
}