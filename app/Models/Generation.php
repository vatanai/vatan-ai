<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Generation extends Model
{
    // 🟢 فیلد prompt به انتهای آرایه fillable اضافه شد تا اجازه ذخیره دیتای JSON صادر شود
    protected $fillable = [
        'user_id', 
        'product_id', 
        'prompt', 
        'input_image', 
        'output_image', 
        'status'
    ];

    // یا می‌توانید به جای fillable از guarded استفاده کنید تا همه‌ فیلدها همیشه مجاز باشند:
    // protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}