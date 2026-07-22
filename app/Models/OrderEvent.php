<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderEvent extends Model
{
    protected $fillable = ['order_id', 'admin_id', 'type', 'title', 'description', 'metadata'];
    protected $casts = ['metadata' => 'array'];
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function admin(): BelongsTo { return $this->belongsTo(Admin::class); }
}
