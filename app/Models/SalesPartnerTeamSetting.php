<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPartnerTeamSetting extends Model
{
    protected $fillable = [
        'admin_id',
        'daily_contact_target',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'admin_id' => 'integer',
            'daily_contact_target' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
