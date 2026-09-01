<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramSegment extends Model
{
    protected $fillable = ['created_by', 'name', 'definition', 'user_count', 'is_active'];

    protected $casts = [
        'definition' => 'array',
        'user_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
