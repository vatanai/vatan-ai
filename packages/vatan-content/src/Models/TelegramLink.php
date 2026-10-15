<?php

namespace Vatan\Content\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramLink extends Model
{
    protected $table = 'cf_telegram_links';
    protected $guarded = ['id'];
    protected $casts = ['payload' => 'array'];

}
