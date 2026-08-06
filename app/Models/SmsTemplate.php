<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $fillable = ['event_key', 'name', 'body', 'provider_method', 'provider_template_id', 'provider_variables', 'provider_approval_status', 'provider_note', 'provider_submitted_at', 'provider_checked_at', 'is_active', 'is_default', 'sent_count', 'last_sent_at', 'last_test_status', 'last_tested_at'];
    protected $casts = ['provider_variables'=>'array', 'provider_submitted_at'=>'datetime', 'provider_checked_at'=>'datetime', 'is_active'=>'boolean', 'is_default'=>'boolean', 'last_sent_at'=>'datetime', 'last_tested_at'=>'datetime'];

    public function eventLabel(): string
    {
        return config("sms_events.events.{$this->event_key}.label", $this->event_key);
    }
}
