<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTestRun extends Model
{
    protected $guarded = [];

    protected $casts = [
        'input_values' => 'array',
        'parameters' => 'array',
        'is_favorite' => 'boolean',
        'rating' => 'integer',
        'duration_ms' => 'integer',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'total_tokens' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
