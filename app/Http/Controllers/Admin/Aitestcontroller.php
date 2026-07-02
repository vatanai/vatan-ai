<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Services\OpenRouterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AiTestController extends Controller
{
    public function __construct(protected OpenRouterService $openRouter)
    {
    }

    /**
     * تست مستقیم پرامپت از پنل ادمین (صفحه افزودن محصول — گام دوم)
     * پرامپت را می‌گیرد، به OpenRouter می‌فرستد، و عکس تولیدشده را برمی‌گرداند.
     * مسیر: POST /admin/ai-models/test-prompt
     */
    public function testPrompt(Request $request)
    {
        $request->validate([
            'prompt'   => 'required|string|max:2000',
            'model_id' => 'required|string|exists:ai_models,model_id',
        ]);

        try {
            $result = $this->openRouter->generateImageFromPrompt(
                $request->model_id,
                $request->prompt,
                $request->get('resolution', '1K'),
                $request->get('aspect_ratio', '1:1'),
                1
            );

            // ذخیره موقت تصویر برای نمایش
            $base64 = $result['data'][0]['b64_json'] ?? null;

            if (!$base64) {
                return response()->json(['success' => false, 'message' => 'تصویری در پاسخ دریافت نشد.'], 422);
            }

            $filename = 'test-previews/' . uniqid('test_') . '.png';
            Storage::disk('public')->put($filename, base64_decode($base64));

            return response()->json([
                'success'   => true,
                'image_url' => asset('storage/' . $filename),
                'model'     => $request->model_id,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * تست یک مدل با عکس ورودی (از صفحه لیست مدل‌ها)
     */
    public function testImage(Request $request, AiModel $aiModel)
    {
        $request->validate([
            'prompt' => 'required|string|max:1000',
        ]);

        try {
            $result = $this->openRouter->generateImage(
                $aiModel,
                $request->input('prompt'),
                ['resolution' => '1K', 'aspect_ratio' => '1:1', 'n' => 1]
            );

            return response()->json([
                'success'    => true,
                'used_model' => $result['model'] ?? $aiModel->model_id,
                'output'     => $result['data'] ?? $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 503);
        }
    }
}