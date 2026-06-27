<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedImage extends Model
{
    protected $fillable = [
        'user_id',
        'prompt_id',
        'image_path',
        'user_prompt',
        'cost'
    ];

    /**
     * رابطه معکوس با کاربر
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * رابطه معکوس با پرامپت/سبک اولیه
     */
    public function prompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class);
    }
}