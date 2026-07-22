<?php

namespace App\Http\Requests\HomeBuilder;

use App\Models\HomeSection;
use Illuminate\Foundation\Http\FormRequest;

class StoreHomeSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:' . implode(',', HomeSection::TYPES),
            'layout' => 'nullable|string|max:50',
            'title_fa' => 'nullable|string|max:255',
            'subtitle_fa' => 'nullable|string|max:255',
            'settings' => 'nullable|array',
            'responsive' => 'nullable|array',
            'responsive.desktop' => 'nullable|boolean',
            'responsive.tablet' => 'nullable|boolean',
            'responsive.mobile' => 'nullable|boolean',
            'responsive.mobile_layout' => 'nullable|string|max:50',
        ];
    }
}
