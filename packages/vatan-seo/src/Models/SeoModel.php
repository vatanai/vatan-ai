<?php

namespace Vatan\Seo\Models;

use Illuminate\Database\Eloquent\Model;

/** پایه‌ی همه‌ی مدل‌های موتور سئو — بدون فیلد محافظت‌شده، چون ورودی‌ها همیشه از سرویس‌ها می‌آیند. */
abstract class SeoModel extends Model
{
    protected $guarded = ['id'];
}
