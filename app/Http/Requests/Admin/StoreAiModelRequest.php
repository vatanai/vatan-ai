<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * قوانین اعتبارسنجی ثبت مدل هوش مصنوعی جدید.
 * (استخراج‌شده از AiModelController@store در فرایند استانداردسازی — قوانین بدون تغییر)
 */
class StoreAiModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        // دسترسی از طریق middleware پنل ادمین کنترل می‌شود؛ رفتار قبلی حفظ می‌شود.
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                 => 'required|string|max:255',
            'openrouter_model_id'  => 'required|string|max:255',
            'provider_name'        => 'required|string|max:255',
            'provider'             => 'nullable|string|in:openrouter,liara,fal,replicate',
            'liara_plan'           => 'nullable|string|in:mirzakhani,turing',
            'external_model_id'    => 'nullable|string|max:500',
            'external_version'     => 'nullable|string|max:500',
            'output_modality'      => 'required|string|in:text,image,video,audio',
            'task_type'            => 'nullable|string|in:text_to_image,image_to_image,text_to_video,image_to_video,video_to_video,face_consistency,face_animation,upscaling',
            'supports_image_input' => 'nullable',
            'supports_face_identity' => 'nullable',
            'supports_multiple_faces' => 'nullable',
            'supports_audio'       => 'nullable',
            'supports_video_input' => 'nullable',
            'cost_per_generation'  => 'required|numeric|min:0',
            'cost_per_generation_usd' => 'nullable|numeric|min:0|max:100000',
            'default_width'        => 'nullable|numeric|min:1',
            'default_height'       => 'nullable|numeric|min:1',
            'max_resolution'       => 'nullable|string|max:30',
            'max_duration'         => 'nullable|integer|min:1|max:3600',
            'default_parameters'   => 'nullable|string',
            'input_schema'         => 'nullable|json',
            'capability_config'    => 'nullable|json',
            'category_ids'         => 'nullable|array',
            'category_ids.*'       => 'integer|exists:categories,id',
            'pricing_config'       => 'nullable|json',
            'pricing_type'         => 'nullable|string|in:per_generation,per_second,per_megapixel,per_gpu_second,unknown',
            'commercial_use'       => 'nullable',
            'supports_webhook'     => 'nullable|boolean',
            'terms_url'            => 'nullable|url|max:500',
            'data_retention_notes' => 'nullable|string|max:4000',
            'is_active'            => 'nullable',
            'description'          => 'nullable|string',
            'model_image'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ];
    }
}
