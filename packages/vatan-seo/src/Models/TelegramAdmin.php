<?php

namespace Vatan\Seo\Models;

class TelegramAdmin extends SeoModel
{
    protected $table = 'seo_telegram_admins';

    protected function casts(): array
    {
        return ['preferences' => 'array', 'is_active' => 'boolean'];
    }
}
