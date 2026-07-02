<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $fillable = [
        'phone',
        'code',
        'expires_at',
        'used'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean'
    ];

    // متد بررسی معتبر بودن کد
    public function isValid(): bool
    {
        return !$this->used && $this->expires_at->isFuture();
    }
}