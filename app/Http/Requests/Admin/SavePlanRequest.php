<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    protected function prepareForValidation(): void
    {
        $numeric = fn ($value) => $value === null || $value === ''
            ? null
            : (int) preg_replace('/\\D+/', '', strtr((string) $value, [
                '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            ]));

        $this->merge([
            'price' => $numeric($this->input('price')) ?? 0,
            'compare_at_price' => $numeric($this->input('compare_at_price')),
            'loyal_price' => $numeric($this->input('loyal_price')),
            'loyal_tokens' => $numeric($this->input('loyal_tokens')),
            'loyal_bonus_tokens' => $numeric($this->input('loyal_bonus_tokens')) ?? 0,
            'is_unlimited' => $this->boolean('is_unlimited'),
            'is_featured' => $this->boolean('is_featured'),
            'loyal_visible' => $this->boolean('loyal_visible'),
            'loyal_purchasable' => $this->boolean('loyal_purchasable'),
        ]);
    }

    public function rules(): array
    {
        $planId = $this->route('plan')?->id ?? $this->route('plan');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('plans', 'slug')->ignore($planId)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'integer', 'min:0'],
            'price_prefix' => ['nullable', 'string', 'max:30'],
            'compare_at_price' => ['nullable', 'integer', 'min:0'],
            'tokens' => ['required', 'integer', 'min:0'],
            'token_label' => ['nullable', 'string', 'max:255'],
            'billing_type' => ['required', Rule::in(['free', 'monthly', 'yearly', 'one_time', 'custom'])],
            'is_unlimited' => ['boolean'],
            'icon' => ['nullable', 'string', 'max:100'],
            'card_style' => ['required', Rule::in(array_keys(config('plan_card_styles', [])))],
            'badge_text' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_featured' => ['boolean'],
            'purchase_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'features' => ['required', 'array', 'min:1'],
            'features.*.title' => ['required', 'string', 'max:255'],
            'features.*.value' => ['nullable', 'string', 'max:255'],
            'features.*.included' => ['required', Rule::in(['yes', 'no', 'limited'])],
            'features.*.highlighted' => ['nullable', 'boolean'],
            'loyal_price' => ['nullable', 'integer', 'min:0'],
            'loyal_tokens' => ['nullable', 'integer', 'min:0'],
            'loyal_bonus_tokens' => ['nullable', 'integer', 'min:0'],
            'loyal_visible' => ['boolean'],
            'loyal_purchasable' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'features.required' => 'حداقل یک قابلیت برای پلن وارد کنید.',
            'features.*.title.required' => 'عنوان همه قابلیت‌ها باید وارد شود.',
            'ends_at.after_or_equal' => 'زمان پایان فروش باید بعد از زمان شروع باشد.',
        ];
    }
}
