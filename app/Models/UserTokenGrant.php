<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * دسته‌های اعتبار هدیه با تاریخ انقضای مستقل.
 * موجودی نهایی همچنان در users.tokens نگه‌داری می‌شود؛ این جدول فقط
 * سهم‌های هدیه‌ای را که باید منقضی و به ترتیب مصرف شوند، نگه می‌دارد.
 */
class UserTokenGrant extends Model
{
    protected $fillable = [
        'user_id', 'token_log_id', 'amount', 'remaining_amount',
        'expires_at', 'source', 'admin_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'remaining_amount' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function tokenLog(): BelongsTo { return $this->belongsTo(TokenLog::class); }
    public function admin(): BelongsTo { return $this->belongsTo(Admin::class); }
}
