<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Services\ProductBuildSchema;
use Illuminate\Foundation\Http\FormRequest;

class GenerateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $product = $this->route('product');
        return $product instanceof Product
            ? app(ProductBuildSchema::class)->rules($product) + [
                'studio_prompt' => ['nullable', 'string', 'max:5000'],
                'studio_negative_prompt' => ['nullable', 'string', 'max:2000'],
                'studio_project_name' => ['nullable', 'string', 'max:120'],
                'studio_model' => ['nullable', 'string', 'max:200'],
                'studio_provider' => ['nullable', 'string', 'max:40'],
                'output.count' => ['nullable', 'integer', 'min:1', 'max:6'],
            ]
            : [];
    }

    public function messages(): array
    {
        return ['required' => 'فیلد «:attribute» الزامی است.', 'image' => 'فایل انتخاب‌شده باید تصویر باشد.', 'max' => 'مقدار یا حجم «:attribute» بیشتر از حد مجاز است.', 'in' => 'مقدار انتخاب‌شده معتبر نیست.'];
    }
}
