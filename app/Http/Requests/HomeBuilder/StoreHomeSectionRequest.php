<?php

namespace App\Http\Requests\HomeBuilder;

use App\Models\HomeSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHomeSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        $type = (string) $this->input('type');
        $layouts = array_keys((array) config("home_builder.types.{$type}.layouts", []));

        return [
            'type' => 'required|string|in:' . implode(',', HomeSection::TYPES),
            'layout' => ['nullable', 'string', Rule::in($layouts)],
            'title_fa' => 'nullable|string|max:255',
            'subtitle_fa' => 'nullable|string|max:255',
            'settings' => 'nullable|array',
            'responsive' => 'nullable|array',
            'responsive.desktop' => 'nullable|boolean',
            'responsive.tablet' => 'nullable|boolean',
            'responsive.mobile' => 'nullable|boolean',
            'responsive.mobile_layout' => ['nullable', 'string', Rule::in($layouts)],
        ];
    }
}
