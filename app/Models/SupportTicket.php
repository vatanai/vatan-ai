<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $fillable = [
        'ticket_number', 'user_id', 'assigned_admin_id', 'channel', 'external_thread_id', 'customer_handle', 'metadata', 'category',
        'priority', 'status', 'subject', 'last_message_preview', 'last_message_at', 'closed_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function assignedAdmin(): BelongsTo { return $this->belongsTo(Admin::class, 'assigned_admin_id'); }
    public function messages(): HasMany { return $this->hasMany(SupportTicketMessage::class); }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'در انتظار پاسخ',
            'answered' => 'پاسخ داده شده',
            'closed' => 'بسته شده',
            default => 'باز',
        };
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'image' => 'ساخت عکس', 'video' => 'ساخت ویدیو', 'payment' => 'پرداخت و اعتبار',
            'account' => 'حساب کاربری', default => 'سایر موارد',
        };
    }
}
