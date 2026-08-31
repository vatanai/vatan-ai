<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneratedVideo;
use App\Models\Product;
use App\Models\ProductTestRun;
use App\Models\VideoHookInspiration;
use App\Models\VideoStudioJob;
use App\Models\VideoStudioSetting;
use App\Support\Jalali;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VideoStudioController extends Controller
{
    public function index(Request $request)
    {
        $hasGeneratedVideos = Schema::hasTable('generated_videos');
        $hasProductRuns = Schema::hasTable('product_test_runs');

        $videoQuery = static fn () => GeneratedVideo::query();
        $videoCount = $hasGeneratedVideos ? $videoQuery()->count() : 0;
        $completedCount = $hasGeneratedVideos ? $videoQuery()->where('status', 'completed')->count() : 0;
        $failedCount = $hasGeneratedVideos ? $videoQuery()->whereIn('status', ['failed', 'error'])->count() : 0;
        $processingCount = $hasGeneratedVideos ? $videoQuery()->whereIn('status', ['queued', 'processing'])->count() : 0;

        $activeProducts = Product::query()->where('status', 'active')->count();
        $coveredProducts = $hasGeneratedVideos
            ? GeneratedVideo::query()->whereNotNull('product_id')->distinct('product_id')->count('product_id')
            : 0;

        $latestVideos = $hasGeneratedVideos
            ? GeneratedVideo::query()->with('product')->latest()->limit(10)->get()
            : collect();
        $latestTests = $hasProductRuns
            ? ProductTestRun::query()->with('product')->latest()->limit(8)->get()
            : collect();

        $productSearch = trim((string) $request->query('product_search', ''));
        $productSort = (string) $request->query('product_sort', 'newest');
        $productSort = in_array($productSort, ['newest', 'oldest', 'name_asc', 'name_desc'], true)
            ? $productSort
            : 'newest';
        $productsQuery = Product::query()->where('status', 'active');
        if ($productSearch !== '') {
            $productsQuery->where(function ($query) use ($productSearch): void {
                $query->where('name_fa', 'like', '%' . $productSearch . '%')
                    ->orWhere('name_en', 'like', '%' . $productSearch . '%')
                    ->orWhere('slug', 'like', '%' . $productSearch . '%');
            });
        }
        $productsQuery->when($productSort === 'oldest', fn ($query) => $query->orderBy('created_at'), function ($query) use ($productSort): void {
            if ($productSort === 'name_asc') {
                $query->orderBy('name_fa');
            } elseif ($productSort === 'name_desc') {
                $query->orderByDesc('name_fa');
            } else {
                $query->orderByDesc('created_at');
            }
        });
        $products = $productsQuery
            ->get(['id', 'name_fa', 'slug', 'created_at']);
        $selectedProductId = $request->integer('product_id') ?: null;
        $selectedProduct = $selectedProductId ? Product::find($selectedProductId) : null;
        $productImages = collect($selectedProduct ? array_merge(
            [$selectedProduct->cover, $selectedProduct->thumbnail],
            (array) $selectedProduct->sample_outputs,
            (array) $selectedProduct->before_images,
        ) : [])->filter()->unique()->map(function ($path): ?array {
            $path = (string) $path;
            $url = Str::startsWith($path, ['http://', 'https://', 'data:'])
                ? $path
                : (Storage::disk('public')->exists($path) ? asset('storage/' . ltrim($path, '/')) : null);
            return $url ? ['path' => $path, 'url' => $url] : null;
        })->filter()->values();
        $settings = VideoStudioSetting::query()
            ->where(function ($query) use ($selectedProductId): void {
                $query->where('product_id', $selectedProductId)
                    ->orWhereNull('product_id');
            })
            ->orderByRaw('product_id IS NULL')
            ->first();
        $settings ??= new VideoStudioSetting([
            'source_mode' => 'auto',
            'auto_enabled' => false,
            'approval_required' => true,
            'auto_generate_hook' => true,
            'auto_generate_caption' => true,
            'auto_generate_keyword' => true,
            'font_family' => 'B_Yekan',
            'aspect_ratio' => '9:16',
        ]);
        if (blank($settings->prompt_profile)) {
            $defaultPromptPath = resource_path('prompts/instagram-video.md');
            if (is_file($defaultPromptPath)) {
                $settings->prompt_profile = trim((string) file_get_contents($defaultPromptPath));
            }
        }
        $hookInspirations = Schema::hasTable('video_hook_inspirations')
            ? VideoHookInspiration::query()->with('product')->where('is_active', true)->latest()->limit(12)->get()
            : collect();
        $jobs = Schema::hasTable('video_studio_jobs')
            ? VideoStudioJob::query()->with('product')->latest()->limit(20)->get()
            : collect();
        $producedProducts = $jobs
            ->filter(fn (VideoStudioJob $job): bool => in_array((string) $job->status, ['queued', 'processing', 'completed'], true))
            ->unique('product_id')
            ->values();
        $coveredProducts = max($coveredProducts, $producedProducts->count());

        $daily = collect(range(13, 0))->map(function (int $daysAgo) use ($hasGeneratedVideos): array {
            $date = now()->subDays($daysAgo)->startOfDay();
            $count = $hasGeneratedVideos
                ? GeneratedVideo::query()->whereDate('created_at', $date)->count()
                : 0;

            [$jy, $jm, $jd] = Jalali::toJalaliYmd((int) $date->format('Y'), (int) $date->format('n'), (int) $date->format('j'));

            return [
                'label' => Jalali::toPersianDigits(sprintf('%02d/%02d', $jm, $jd)),
                'count' => $count,
            ];
        });

        return view('admin.video-studio.index', [
            'videoCount' => $videoCount,
            'completedCount' => $completedCount,
            'failedCount' => $failedCount,
            'processingCount' => $processingCount,
            'activeProducts' => $activeProducts,
            'coveredProducts' => $coveredProducts,
            'latestVideos' => $latestVideos,
            'latestTests' => $latestTests,
            'products' => $products,
            'productSearch' => $productSearch,
            'productSort' => $productSort,
            'selectedProductId' => $selectedProductId,
            'selectedProduct' => $selectedProduct,
            'productImages' => $productImages,
            'settings' => $settings,
            'hookInspirations' => $hookInspirations,
            'jobs' => $jobs,
            'producedProducts' => $producedProducts,
            'daily' => $daily,
            'dataSources' => [
                'products' => true,
                'generated_videos' => $hasGeneratedVideos,
                'product_test_runs' => $hasProductRuns,
            ],
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'source_mode' => ['required', 'in:auto,upload,music,video'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'auto_enabled' => ['nullable', 'boolean'],
            'approval_required' => ['nullable', 'boolean'],
            'auto_generate_hook' => ['nullable', 'boolean'],
            'auto_generate_caption' => ['nullable', 'boolean'],
            'auto_generate_keyword' => ['nullable', 'boolean'],
            'hook_guidelines' => ['nullable', 'string', 'max:5000'],
            'caption_guidelines' => ['nullable', 'string', 'max:5000'],
            'hook_text' => ['nullable', 'string', 'max:1000'],
            'caption_text' => ['nullable', 'string', 'max:5000'],
            'prompt_profile' => ['nullable', 'string', 'max:30000'],
            'prompt_file' => ['nullable', 'file', 'mimes:txt,md', 'max:512'],
            'keyword' => ['nullable', 'string', 'max:80'],
            'dm_template' => ['nullable', 'string', 'max:5000'],
            'font_family' => ['required', 'in:B_Yekan'],
            'aspect_ratio' => ['required', 'in:9:16,1:1,4:5,16:9'],
        ]);

        $product = Product::query()->findOrFail((int) $data['product_id']);
        $productLink = route('app.product', ['product' => $product->route_slug]);

        $productId = $data['product_id'] ?? null;
        if ($request->hasFile('prompt_file')) {
            $profileText = trim((string) file_get_contents($request->file('prompt_file')->getRealPath()));
            if ($profileText !== '') {
                $data['prompt_profile'] = $profileText;
            }
        }
        unset($data['prompt_file']);
        $setting = VideoStudioSetting::query()
            ->where('product_id', $productId)
            ->first();
        $setting ??= new VideoStudioSetting(['product_id' => $productId]);
        $setting->fill($data);
        $setting->product_id = $productId;
        $setting->auto_enabled = $request->boolean('auto_enabled');
        $setting->approval_required = $request->boolean('approval_required', true);
        $setting->auto_generate_hook = $request->boolean('auto_generate_hook');
        $setting->auto_generate_caption = $request->boolean('auto_generate_caption');
        $setting->auto_generate_keyword = $request->boolean('auto_generate_keyword');
        $setting->save();

        return redirect()
            ->route('admin.products.dashboard', $productId ? ['product_id' => $productId] : [])
            ->with('success', 'تنظیمات ساخت ویدیو ذخیره شد.');
    }

    public function storeHook(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'title' => ['required', 'string', 'max:160'],
            'hook_text' => ['required', 'string', 'max:1000'],
            'tags' => ['nullable', 'string', 'max:500'],
        ]);
        VideoHookInspiration::create($data);

        return back()->with('success', 'هوک به کتابخانه ایده‌ها اضافه شد.');
    }

    public function destroyHook(VideoHookInspiration $hook)
    {
        $hook->delete();

        return back()->with('success', 'هوک از کتابخانه حذف شد.');
    }

    public function createJob(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'source_mode' => ['required', Rule::in(['auto', 'upload', 'music', 'video'])],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'source_file' => ['nullable', 'file', 'mimes:mp3,wav,m4a,ogg,mp4,mov,webm', 'max:102400'],
            'selected_images' => ['nullable', 'array', 'max:10'],
            'selected_images.*' => ['string', 'max:2048'],
            'aspect_ratio' => ['required', Rule::in(['9:16', '1:1', '4:5', '16:9'])],
            'hook_text' => ['nullable', 'string', 'max:1000'],
            'caption_text' => ['nullable', 'string', 'max:5000'],
            'prompt_profile' => ['nullable', 'string', 'max:30000'],
            'prompt_file' => ['nullable', 'file', 'mimes:txt,md', 'max:512'],
            'keyword' => ['nullable', 'string', 'max:80'],
            'dm_template' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($request->hasFile('source_file')) {
            $data['source_url'] = asset('storage/' . ltrim($request->file('source_file')->store('video-studio/sources', 'public'), '/'));
        }
        if ($request->hasFile('prompt_file')) {
            $profileText = trim((string) file_get_contents($request->file('prompt_file')->getRealPath()));
            if ($profileText !== '') {
                $data['prompt_profile'] = $profileText;
            }
        }
        if (in_array($data['source_mode'], ['upload', 'music', 'video'], true) && blank($data['source_url'])) {
            return back()
                ->withErrors(['source_file' => 'برای این منبع، حتماً فایل یا لینک جدید انتخاب کنید؛ منبع قبلی دوباره استفاده نمی‌شود.'])
                ->withInput();
        }
        unset($data['source_file']);
        unset($data['prompt_file']);
        $autoHook = $request->boolean('auto_generate_hook');
        $autoCaption = $request->boolean('auto_generate_caption');
        $autoKeyword = $request->boolean('auto_generate_keyword');
        if ($autoHook) $data['hook_text'] = null;
        if ($autoCaption) $data['caption_text'] = null;
        if ($autoKeyword) $data['keyword'] = null;
        $sourceFingerprint = hash('sha256', json_encode([
            'product_id' => (int) $data['product_id'],
            'source_mode' => (string) $data['source_mode'],
            'source_url' => (string) ($data['source_url'] ?? ''),
            'selected_images' => array_values($data['selected_images'] ?? []),
            'aspect_ratio' => (string) $data['aspect_ratio'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $recentDuplicate = VideoStudioJob::query()
            ->whereIn('status', ['queued', 'processing'])
            ->latest('id')
            ->limit(50)
            ->get()
            ->first(fn (VideoStudioJob $candidate): bool => (string) data_get($candidate->payload, 'source_fingerprint') === $sourceFingerprint);
        if ($recentDuplicate) {
            return redirect()
                ->route('admin.products.dashboard', ['product_id' => $recentDuplicate->product_id])
                ->with('warning', "این ترکیب محصول و منبع از قبل در صف است (#{$recentDuplicate->id}) و دوباره ارسال نشد.");
        }
        $job = VideoStudioJob::create(array_merge($data, [
            'admin_id' => auth('admin')->id(),
            'status' => 'queued',
            'payload' => [
                'created_from' => 'video_studio_dashboard',
                'auto_generate_hook' => $autoHook,
                'auto_generate_caption' => $autoCaption,
                'auto_generate_keyword' => $autoKeyword,
                'hook_guidelines' => (string) $request->input('hook_guidelines', ''),
                'caption_guidelines' => (string) $request->input('caption_guidelines', ''),
                'prompt_profile' => (string) ($data['prompt_profile'] ?? ''),
                'source_fingerprint' => $sourceFingerprint,
            ],
        ]));

        $this->dispatchJobToWorkflow($job);

        return redirect()->route('admin.products.dashboard', ['product_id' => $job->product_id])
            ->with('success', $job->status === 'processing' ? 'ساخت ویدیو شروع شد و در صف پردازش قرار گرفت.' : 'سفارش در صف ساخت ثبت شد. اتصال اجرای خودکار هنوز تنظیم نشده است.');
    }

    public function retryJob(VideoStudioJob $job)
    {
        $job->update(['status' => 'queued', 'error_message' => null, 'started_at' => null, 'completed_at' => null]);
        $this->dispatchJobToWorkflow($job);

        return back()->with('success', 'سفارش برای ساخت مجدد به ورکفلو ارسال شد.');
    }

    public function reviseJob(Request $request, VideoStudioJob $job)
    {
        $data = $request->validate([
            'revision_request' => ['required', 'string', 'max:5000'],
        ]);

        $payload = is_array($job->payload) ? $job->payload : [];
        $payload['revision_request'] = trim($data['revision_request']);
        $payload['revision_at'] = now()->toIso8601String();
        $job->update([
            'status' => 'queued',
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
            'payload' => $payload,
        ]);
        $this->dispatchJobToWorkflow($job->fresh());

        return back()->with('success', 'اصلاحیه به هوش مصنوعی ارسال شد و ساخت مجدد آغاز می‌شود.');
    }

    public function bulkAction(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['delete', 'retry'])],
            'job_ids' => ['required', 'array', 'min:1', 'max:50'],
            'job_ids.*' => ['integer', 'exists:video_studio_jobs,id'],
        ]);

        $jobs = VideoStudioJob::query()->whereIn('id', $data['job_ids'])->get();
        if ($data['action'] === 'delete') {
            $jobs->each->delete();
            return back()->with('success', 'سفارش‌های انتخاب‌شده از صف حذف شدند.');
        }

        $jobs->each(function (VideoStudioJob $job): void {
            $job->update(['status' => 'queued', 'error_message' => null, 'started_at' => null, 'completed_at' => null]);
            $this->dispatchJobToWorkflow($job->fresh());
        });

        return back()->with('success', 'سفارش‌های انتخاب‌شده برای ساخت مجدد ارسال شدند.');
    }

    private function dispatchJobToWorkflow(VideoStudioJob $job): void
    {
        $webhook = trim((string) config('services.n8n.video_studio_webhook', env('N8N_VIDEO_STUDIO_WEBHOOK_URL', '')));
        if ($webhook === '') {
            $job->update(['status' => 'failed', 'error_message' => 'اتصال ورکفلو تنظیم نشده است.']);
            return;
        }

        $product = $job->product ?: Product::query()->find($job->product_id);
        $payload = is_array($job->payload) ? $job->payload : [];
        $autoHook = (bool) ($payload['auto_generate_hook'] ?? false);
        $autoCaption = (bool) ($payload['auto_generate_caption'] ?? false);
        $autoKeyword = (bool) ($payload['auto_generate_keyword'] ?? false);
        $promptProfile = trim((string) ($payload['prompt_profile'] ?? ''));
        $hookGuidelines = trim((string) ($payload['hook_guidelines'] ?? ''));
        $captionGuidelines = trim((string) ($payload['caption_guidelines'] ?? ''));
        if ($promptProfile !== '') {
            $hookGuidelines = trim($promptProfile . "\n\n" . $hookGuidelines);
            $captionGuidelines = trim($promptProfile . "\n\n" . $captionGuidelines);
        }
        if (Schema::hasTable('video_hook_inspirations')) {
            $library = VideoHookInspiration::query()
                ->where('is_active', true)
                ->latest()
                ->limit(12)
                ->get(['title', 'hook_text', 'tags'])
                ->map(fn (VideoHookInspiration $item): string => trim(implode(' | ', array_filter([
                    (string) $item->title,
                    (string) $item->hook_text,
                    is_array($item->tags) ? implode('، ', $item->tags) : (string) $item->tags,
                ]))))
                ->filter()
                ->implode("\n");
            if ($library !== '') {
                $hookGuidelines = trim($hookGuidelines . "\n\nنمونه‌های تأییدشدهٔ کتابخانهٔ هوک؛ فقط از ساختارشان الهام بگیر و متن را برای محصول فعلی بازنویسی کن:\n" . $library);
            }
        }
        try {
            $response = Http::retry(3, 300)->timeout(20)->post($webhook, [
                'job_id' => $job->id,
                'product_id' => $job->product_id,
                'product_name' => (string) ($product?->name_fa ?? 'محصول'),
                'product_link' => $product ? route('app.product', ['product' => $product->route_slug]) : '',
                'source_mode' => $job->source_mode,
                'source_url' => $job->source_url,
                'selected_images' => $job->selected_images,
                'aspect_ratio' => $job->aspect_ratio,
                'hook_text' => $job->hook_text,
                'caption_text' => $job->caption_text,
                'keyword' => $job->keyword,
                'dm_template' => $job->dm_template,
                'auto_generate_hook' => $autoHook,
                'auto_generate_caption' => $autoCaption,
                'auto_generate_keyword' => $autoKeyword,
                'hook_guidelines' => $hookGuidelines,
                'caption_guidelines' => $captionGuidelines,
                'prompt_profile' => $promptProfile,
                'revision_request' => (string) ($payload['revision_request'] ?? ''),
                'chat_id' => (string) config('services.n8n.video_studio_telegram_chat_id', ''),
                'callback_url' => rtrim((string) config('services.n8n.video_studio_callback_base_url', ''), '/')
                    . ((string) config('services.n8n.video_studio_callback_base_url', '') !== '' ? '/webhooks/video-studio/' . $job->id . '/status' : route('webhooks.video-studio.status', ['job' => $job->id])),
                'status_secret' => (string) config('services.n8n.video_studio_status_secret', ''),
            ]);
            if ($response->successful()) {
                $job->update(['status' => 'processing', 'n8n_execution_id' => (string) ($response->json('execution_id') ?? '')]);
            } else {
                $job->update(['status' => 'failed', 'error_message' => 'پاسخ نامعتبر از ورکفلو دریافت شد.']);
            }
        } catch (\Throwable $e) {
            $job->update(['status' => 'failed', 'error_message' => 'اتصال به ورکفلو برقرار نشد.']);
        }
    }

    /**
     * دریافت وضعیت اجرای ورکفلو از `n8n`؛ این مسیر عمومی است اما با secret امضا می‌شود.
     */
    public function n8nStatus(Request $request, VideoStudioJob $job)
    {
        $expected = trim((string) config('services.n8n.video_studio_status_secret', ''));
        $provided = trim((string) $request->header('X-N8N-Webhook-Secret', ''));
        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['message' => 'دسترسی غیرمجاز است.'], 401);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(['queued', 'processing', 'completed', 'failed'])],
            'n8n_execution_id' => ['nullable', 'string', 'max:120'],
            'video_url' => ['nullable', 'url', 'max:2048'],
            'error_message' => ['nullable', 'string', 'max:5000'],
        ]);

        $status = $data['status'];
        $job->update([
            'status' => $status,
            'n8n_execution_id' => $data['n8n_execution_id'] ?? $job->n8n_execution_id,
            'video_url' => $data['video_url'] ?? $job->video_url,
            'error_message' => $data['error_message'] ?? null,
            'started_at' => in_array($status, ['processing', 'completed'], true) ? ($job->started_at ?: now()) : $job->started_at,
            'completed_at' => in_array($status, ['completed', 'failed'], true) ? now() : null,
        ]);

        return response()->json(['ok' => true, 'job_id' => $job->id, 'status' => $job->status]);
    }
}
