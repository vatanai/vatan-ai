<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceVendor extends Model
{
    protected $fillable = [
        'name', 'contact_name', 'phone', 'email', 'tax_id', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
