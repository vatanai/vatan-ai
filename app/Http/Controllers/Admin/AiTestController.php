<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Product;
use App\Models\ProductTestRun;
use App\Services\AiProviderRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiTestController extends Controller
{
    public function __construct(protected AiProviderRouter $openRouter)
    {
    }

    /**
     * تست مستقیم پرامپت از پنل ادمین (صفحه افزودن محصول — گام دوم)
     * پرامپت را می‌گیرد، به سرویس مناسب (لیارا یا OpenRouter) می‌فرستد، و عکس تولیدشده را برمی‌گرداند.
     * مسیر: POST /admin/ai-models/test-prompt
     */
    public function testPrompt(Request $request)
    {
        if ($request->input('product_id') === 'null') {
            $request->merge(['product_id' => null]);
        }
        $request->validate([
            'prompt'   => 'required|string|max:12000',
            'model_id' => 'required|string|exists:ai_models,openrouter_model_id',
            'product_id' => 'nullable|integer|exists:products,id',
            'draft_uuid' => 'nullable|uuid',
            'batch_uuid' => 'required|uuid',
            'mode' => 'required|in:quick,compare',
            'input_values_json' => 'nullable|string|max:30000',
            'prompt_template' => 'nullable|string|max:12000',
            'negative_prompt' => 'nullable|string|max:4000',
            'resolution' => 'nullable|string|max:20',
            'aspect_ratio' => 'nullable|string|max:20',
            'seed' => 'nullable|integer',
            'reference_images' => 'nullable|array|max:20',
            'reference_images.*' => 'image|max:20480',
        ]);

        $inputs = json_decode((string) $request->input('input_values_json', '{}'), true);
        if (!is_array($inputs)) $inputs = [];
        $startedAt = microtime(true);
        $run = ProductTestRun::create([
            'batch_uuid' => $request->input('batch_uuid'),
            'draft_uuid' => $request->input('draft_uuid'),
            'product_id' => $request->input('product_id'),
            'admin_id' => auth('admin')->id(),
            'mode' => $request->input('mode'),
            'model_id' => $request->input('model_id'),
            'status' => 'processing',
            'input_values' => $inputs,
            'prompt_template' => $request->input('prompt_template'),
            'final_prompt' => $request->input('prompt'),
            'negative_prompt' => $request->input('negative_prompt'),
            'parameters' => $request->only(['resolution', 'aspect_ratio', 'seed']),
        ]);

        try {
            $references = [];
            foreach ($request->file('reference_images', []) as $image) {
                $references[] = 'data:' . $image->getMimeType() . ';base64,' . base64_encode(file_get_contents($image->getRealPath()));
            }

            $extra = array_filter(['seed' => $request->input('seed')], fn ($value) => $value !== null && $value !== '');
            if ($references) {
                // تست پنل باید دقیقاً همان Image API رسمیِ مسیر ساخت محصول را
                // استفاده کند. مسیر chat/completions برای مدل‌های image-only و
                // برخی providerها خروجی تصویری تضمین‌شده ندارد.
                $extra['input_references'] = array_map(fn (string $image) => [
                    'type' => 'image_url',
                    'image_url' => ['url' => $image],
                ], $references);
            }
            $result = $this->openRouter->generateImageFromPrompt(
                $request->model_id,
                $request->prompt,
                $request->get('resolution', '1K'),
                $request->get('aspect_ratio', '1:1'),
                1,
                $extra
            );

            [$base64, $remoteUrl] = $this->extractImage($result);

            if (!$base64 && !$remoteUrl) {
                $run->update([
                    'status' => 'failed',
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                    'error_message' => 'تصویری در پاسخ دریافت نشد.',
                ]);
                return response()->json(['success' => false, 'message' => 'تصویری در پاسخ دریافت نشد.'], 422);
            }

            $filename = null;
            $imageUrl = $remoteUrl;
            if ($base64) {
                $filename = 'test-previews/' . Str::uuid() . '.png';
                Storage::disk('public')->put($filename, base64_decode($base64));
                $imageUrl = asset('storage/' . $filename);
            }

            $usage = $this->extractUsage($result);
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $run->update([
                'status' => 'completed',
                'output_path' => $filename ?: $remoteUrl,
                'duration_ms' => $durationMs,
                'input_tokens' => $usage['input_tokens'],
                'output_tokens' => $usage['output_tokens'],
                'total_tokens' => $usage['total_tokens'],
            ]);
            $this->refreshProductTestStats($run->product_id);

            return response()->json([
                'success'   => true,
                'run_id' => $run->id,
                'image_url' => $imageUrl,
                'model'     => $request->model_id,
                'duration_ms' => $durationMs,
                'usage' => $usage,
                'created_at' => $run->created_at?->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            $run->update([
                'status' => 'failed',
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'error_message' => Str::limit($e->getMessage(), 4000, ''),
            ]);
            return response()->json([
                'success' => false,
                'run_id' => $run->id,
                'message' => $e->getMessage(),
            ], 503);
        }
    }

    public function history(Request $request)
    {
        $query = ProductTestRun::query()->latest()->limit(50);
        if ($request->filled('product_id')) $query->where('product_id', $request->integer('product_id'));
        elseif ($request->filled('draft_uuid')) $query->where('draft_uuid', $request->input('draft_uuid'));
        else return response()->json(['runs' => []]);

        return response()->json(['runs' => $query->get()->map(fn ($run) => $this->serializeRun($run))]);
    }

    public function updateRun(Request $request, ProductTestRun $run)
    {
        $data = $request->validate([
            'rating' => 'nullable|integer|min:1|max:5',
            'note' => 'nullable|string|max:2000',
            'is_favorite' => 'nullable|boolean',
        ]);
        $run->update($data);
        return response()->json(['success' => true, 'run' => $this->serializeRun($run->fresh())]);
    }

    private function extractImage(array $result): array
    {
        $base64 = data_get($result, 'data.0.b64_json');
        $url = data_get($result, 'data.0.url');
        $chatImage = data_get($result, 'choices.0.message.images.0.image_url.url')
            ?? data_get($result, 'choices.0.message.images.0.url');
        if (is_string($chatImage) && str_starts_with($chatImage, 'data:')) {
            $base64 = preg_replace('/^data:[^;]+;base64,/', '', $chatImage);
            $chatImage = null;
        }
        return [$base64, $url ?: $chatImage];
    }

    private function extractUsage(array $result): array
    {
        $usage = $result['usage'] ?? data_get($result, 'data.usage') ?? [];
        $input = $usage['prompt_tokens'] ?? $usage['input_tokens'] ?? null;
        $output = $usage['completion_tokens'] ?? $usage['output_tokens'] ?? null;
        $total = $usage['total_tokens'] ?? (($input !== null && $output !== null) ? $input + $output : null);
        return [
            'input_tokens' => is_numeric($input) ? (int) $input : null,
            'output_tokens' => is_numeric($output) ? (int) $output : null,
            'total_tokens' => is_numeric($total) ? (int) $total : null,
        ];
    }

    private function refreshProductTestStats(?int $productId): void
    {
        if (!$productId) return;
        $latestDuration = ProductTestRun::where('product_id', $productId)->where('status', 'completed')->latest()->value('duration_ms');
        $totalTokens = ProductTestRun::where('product_id', $productId)->where('status', 'completed')->sum('total_tokens');
        Product::whereKey($productId)->update(['last_test_duration_ms' => $latestDuration, 'total_test_tokens' => $totalTokens]);
    }

    private function serializeRun(ProductTestRun $run): array
    {
        $path = $run->output_path;
        $url = $path && !str_starts_with($path, 'http') ? asset('storage/' . $path) : $path;
        return [
            'id' => $run->id, 'batch_uuid' => $run->batch_uuid, 'mode' => $run->mode,
            'model' => $run->model_id, 'status' => $run->status, 'image_url' => $url,
            'duration_ms' => $run->duration_ms, 'total_tokens' => $run->total_tokens,
            'rating' => $run->rating, 'note' => $run->note, 'is_favorite' => $run->is_favorite,
            'final_prompt' => $run->final_prompt, 'input_values' => $run->input_values,
            'error_message' => $run->error_message, 'created_at' => $run->created_at?->toIso8601String(),
        ];
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
                'used_model' => $result['model'] ?? $aiModel->openrouter_model_id,
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
