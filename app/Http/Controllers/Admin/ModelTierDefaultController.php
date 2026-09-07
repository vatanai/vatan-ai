<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\ModelTierDefault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ModelTierDefaultController extends Controller
{
    public function update(Request $request, ModelTierDefault $modelTierDefault): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:300'],
            'primary_model_id' => ['required', 'string', 'max:255'],
            'primary_provider' => ['required', Rule::in(['openrouter', 'fal', 'replicate'])],
            'fallback_model_id' => ['required', 'string', 'max:255'],
            'fallback_provider' => ['required', Rule::in(['openrouter', 'fal', 'replicate'])],
            'is_active' => ['required', 'boolean'],
        ]);

        $this->validateModelPair($data);
        $modelTierDefault->update($data);

        return response()->json([
            'ok' => true,
            'tier' => $modelTierDefault->fresh(),
            'message' => 'پیش‌فرض سطح مدل ذخیره شد.',
        ]);
    }

    private function validateModelPair(array $data): void
    {
        foreach (['primary', 'fallback'] as $kind) {
            $exists = AiModel::query()
                ->where('is_active', true)
                ->where('featured_in_lab', true)
                ->where('provider', $data[$kind . '_provider'])
                ->where('openrouter_model_id', $data[$kind . '_model_id'])
                ->exists();

            if (! $exists) {
                throw ValidationException::withMessages([
                    $kind . '_model_id' => 'مدل انتخاب‌شده فعال یا معتبر نیست.',
                ]);
            }
        }

        if ($data['primary_provider'] === $data['fallback_provider']
            && $data['primary_model_id'] === $data['fallback_model_id']) {
            throw ValidationException::withMessages([
                'fallback_model_id' => 'مدل جایگزین نمی‌تواند همان مدل اصلی باشد.',
            ]);
        }
    }
}
