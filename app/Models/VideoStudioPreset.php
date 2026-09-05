<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoStudioPreset extends Model
{
    protected $fillable = ['admin_id', 'name', 'settings'];

    protected $casts = ['settings' => 'array'];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
