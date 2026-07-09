<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPromptHistory extends Model
{
    protected $table = 'product_prompt_histories';

    protected $fillable = [
        'product_id', 
        'prompt_text', 
        'version_number', 
        'user_id'
    ];

    /**
     * ارتباط معکوس با محصول
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * ارتباط با کاربری (ادمینی) که پرامپت را تغییر داده است
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}