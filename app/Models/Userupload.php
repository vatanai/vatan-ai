<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserUpload extends Model
{
    protected $fillable = ['user_id', 'file_path', 'size', 'mime_type'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}