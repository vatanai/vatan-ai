<?php

namespace App\Http\Requests\HomeBuilder;

use App\Models\HomeSection;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHomeSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            // نوع Section بعد از ایجاد قابل تغییر نیست (settings هر نوع متفاوت است) — عمداً در قوانین نیست.
            'layout' => 'nullable|string|max:50',
            'title_fa' => 'nullable|string|max:255',
            'subtitle_fa' => 'nullable|string|max:255',
            'settings' => 'nullable|array',
            'responsive' => 'nullable|array',
            'responsive.desktop' => 'nullable|boolean',
            'responsive.tablet' => 'nullable|boolean',
            'responsive.mobile' => 'nullable|boolean',
            'responsive.mobile_layout' => 'nullable|string|max:50',
            'status' => 'nullable|in:' . implode(',', HomeSection::STATUSES),
        ];
    }
}
