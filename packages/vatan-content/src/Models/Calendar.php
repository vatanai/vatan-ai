<?php

namespace Vatan\Content\Models;

use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    protected $table = 'cf_calendars';
    protected $guarded = ['id'];
    protected $casts = ['summary' => 'array', 'brief' => 'array', 'starts_on' => 'date', 'ends_on' => 'date', 'approved_at' => 'datetime'];

    public function items() { return $this->hasMany(CalendarItem::class, 'calendar_id')->orderBy('sort'); }
    public function isLocked(): bool { return $this->status === 'approved'; }
}
