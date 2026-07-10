<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    protected $table = 'ai_models';

    protected $fillable = [
        'name',
        'openrouter_model_id',
        'provider_name',
        'output_modality',
        'supports_image_input',
        'cost_per_generation',
        'default_width',
        'default_height',
        'default_parameters',
        'description',
        'is_active'
    ];

    protected $casts = [
        'supports_image_input' => 'boolean',
        'is_active' => 'boolean',
        'cost_per_generation' => 'integer',
        'default_width' => 'integer',
        'default_height' => 'integer',
    ];

    /**
     * دریافت آدرس هوشمند لوگو/عکس مدل از پوشه سرور بر اساس شناسه OpenRouter
     */
    public function getImageUrlAttribute()
    {
        $safeName = str_replace(['/', '\\', ':', '*'], '-', $this->openrouter_model_id);
        
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $path = 'uploads/models/' . $safeName . '.' . $ext;
            if (file_exists(public_path($path))) {
                return asset($path);
            }
        }

        // تصویر موقت در صورت عدم آپلود عکس اختصاصی
        return 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100%" height="100%" fill="%23222230"/><text x="50%" y="55%" font-family="sans-serif" font-size="20" fill="%23555570" text-anchor="middle">AI Model</text></svg>';
    }
}