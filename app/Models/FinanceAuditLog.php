<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'auditable_type', 'auditable_id', 'action', 'admin_id', 'before_data',
        'after_data', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return ['before_data' => 'array', 'after_data' => 'array'];
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
