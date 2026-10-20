<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImportItem extends Model
{
    protected $fillable = [
        'run_id', 'source_url', 'source_key', 'source_title', 'payload', 'status',
        'products_created', 'product_ids', 'error_message', 'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'product_ids' => 'array',
        'products_created' => 'integer',
        'processed_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(ProductImportRun::class, 'run_id');
    }
}
