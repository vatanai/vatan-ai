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
        return $product instanceof Product ? app(ProductBuildSchema::class)->rules($product) : [];
    }

    public function messages(): array
    {
        return ['required' => 'فیلد «:attribute» الزامی است.', 'image' => 'فایل انتخاب‌شده باید تصویر باشد.', 'max' => 'مقدار یا حجم «:attribute» بیشتر از حد مجاز است.', 'in' => 'مقدار انتخاب‌شده معتبر نیست.'];
    }
}
