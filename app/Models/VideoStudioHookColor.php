<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoStudioHookColor extends Model
{
    protected $fillable = ['admin_id', 'target', 'name', 'color_value'];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
