<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $fillable = ['event_key', 'name', 'body', 'is_active', 'is_default', 'sent_count', 'last_sent_at'];
    protected $casts = ['is_active'=>'boolean', 'is_default'=>'boolean', 'last_sent_at'=>'datetime'];

    public function eventLabel(): string
    {
        return config("sms_events.events.{$this->event_key}.label", $this->event_key);
    }
}
