<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'admins'; // یا 'users' اگر از همان جدول استفاده می‌کنید

    protected $fillable = [
        'name', 'email', 'phone', 'role', 'is_active', 'password', 'password_reveal', 'remember_token',
    ];

    protected $hidden = [
        'password', 'password_reveal', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'password_reveal' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function isLeader(): bool
    {
        return $this->role === 'leader';
    }
}
