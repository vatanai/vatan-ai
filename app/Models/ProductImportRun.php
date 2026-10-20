<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductImportRun extends Model
{
    protected $fillable = [
        'admin_id', 'source_url', 'source_host', 'adapter', 'prompt_amendment', 'text_model', 'status', 'discovered_count',
        'processed_count', 'product_count', 'failed_count', 'last_error',
    ];

    protected $casts = [
        'discovered_count' => 'integer',
        'processed_count' => 'integer',
        'product_count' => 'integer',
        'failed_count' => 'integer',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductImportItem::class, 'run_id');
    }
}
