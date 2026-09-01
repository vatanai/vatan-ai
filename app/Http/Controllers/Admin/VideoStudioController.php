<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneratedVideo;
use App\Models\Product;
use App\Models\ProductTestRun;
use App\Models\VideoHookInspiration;
use App\Models\VideoStudioJob;
use App\Models\VideoStudioSetting;
use App\Models\VideoStudioSource;
use App\Models\VideoStudioFont;
use App\Support\Jalali;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        if (blank($settings->instagram_prompt)) {
            $settings->instagram_prompt = $settings->prompt_profile;
        }
        if (blank($settings->telegram_prompt)) {
            $defaultTelegramPromptPath = resource_path('prompts/telegram-video.md');
            if (is_file($defaultTelegramPromptPath)) {
                $settings->telegram_prompt = trim((string) file_get_contents($defaultTelegramPromptPath));
            }
        }
        $hookInspirations = Schema::hasTable('video_hook_inspirations')
            ? VideoHookInspiration::query()->with('product')->where('is_active', true)->latest()->limit(12)->get()
            : collect();
        $sources = Schema::hasTable('video_studio_sources')
            ? VideoStudioSource::query()->where('is_active', true)->latest()->get()
            : collect();
        $fonts = Schema::hasTable('video_studio_fonts')
            ? VideoStudioFont::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get()
            : collect([
                new VideoStudioFont(['name' => 'یکان', 'slug' => 'B_Yekan', 'file_path' => 'fonts/B_Yekan.ttf', 'is_default' => true]),
            ]);
        $jobs = Schema::hasTable('video_studio_jobs')
            ? VideoStudioJob::query()->with('product')->latest()->limit(20)->get()
            : collect();
        $completedVideoCounts = Schema::hasTable('video_studio_jobs')
            ? VideoStudioJob::query()->where('status', 'completed')->selectRaw('product_id, COUNT(*) AS total')->groupBy('product_id')->pluck('total', 'product_id')->mapWithKeys(fn ($count, $id): array => [(int) $id => (int) $count])->all()
            : [];
        $pendingVideoCounts = Schema::hasTable('video_studio_jobs')
            ? VideoStudioJob::query()->whereIn('status', ['queued', 'processing'])->selectRaw('product_id, COUNT(*) AS total')->groupBy('product_id')->pluck('total', 'product_id')->mapWithKeys(fn ($count, $id): array => [(int) $id => (int) $count])->all()
            : [];
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
            'sources' => $sources,
            'fonts' => $fonts,
            'jobs' => $jobs,
            'completedVideoCounts' => $completedVideoCounts,
            'pendingVideoCounts' => $pendingVideoCounts,
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
            'instagram_prompt' => ['nullable', 'string', 'max:30000'],
            'telegram_prompt' => ['nullable', 'string', 'max:30000'],
            'telegram_buttons_enabled' => ['nullable', 'boolean'],
            'telegram_button_label' => ['nullable', 'array', 'max:8'],
            'telegram_button_label.*' => ['nullable', 'string', 'max:80'],
            'telegram_button_url' => ['nullable', 'array', 'max:8'],
            'telegram_button_url.*' => ['nullable', 'url', 'max:2048'],
            'telegram_button_style' => ['nullable', 'array', 'max:8'],
            'telegram_button_style.*' => ['nullable', Rule::in(['primary', 'success', 'danger'])],
            'prompt_file' => ['nullable', 'file', 'mimes:txt,md', 'max:512'],
            'keyword' => ['nullable', 'string', 'max:80'],
            'dm_template' => ['nullable', 'string', 'max:5000'],
            'font_family' => ['required', Schema::hasTable('video_studio_fonts')
                ? Rule::exists('video_studio_fonts', 'slug')->where('is_active', true)
                : Rule::in(['B_Yekan'])],
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
        $data['telegram_buttons'] = $this->normalizeTelegramButtons($request);
        unset($data['telegram_buttons_enabled'], $data['telegram_button_label'], $data['telegram_button_url'], $data['telegram_button_style']);
        unset($data['prompt_file']);
        $setting = VideoStudioSetting::query()
            ->where('product_id', $productId)
            ->first();
        $setting ??= new VideoStudioSetting(['product_id' => $productId]);
        $setting->fill($data);
        $setting->product_id = $productId;
        $setting->auto_enabled = $request->boolean('auto_enabled');
        // تأیید انسانی فعلاً از مسیر تولید حذف شده و خروجی مستقیماً آمادهٔ ارسال است.
        $setting->approval_required = false;
        $setting->auto_generate_hook = $request->boolean('auto_generate_hook');
        $setting->auto_generate_caption = $request->boolean('auto_generate_caption');
        $setting->auto_generate_keyword = $request->boolean('auto_generate_keyword');
        $setting->save();

        return redirect()
            ->route('admin.products.dashboard', $productId ? ['product_id' => $productId] : [])
            ->with('success', 'تنظیمات ساخت ویدیو ذخیره شد.');
    }

    public function previewContent(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => ['required', 'integer', 'exists:products,id'],
                'hook_guidelines' => ['nullable', 'string', 'max:5000'],
                'caption_guidelines' => ['nullable', 'string', 'max:5000'],
                'instagram_prompt' => ['nullable', 'string', 'max:30000'],
                'telegram_prompt' => ['nullable', 'string', 'max:30000'],
                'channel' => ['nullable', Rule::in(['instagram', 'telegram'])],
            ]);
            $product = Product::query()->find((int) $data['product_id']);
            if (!$product) {
                return response()->json(['message' => 'محصول انتخاب‌شده پیدا نشد؛ دوباره محصول را انتخاب کنید.'], 422);
            }
            $webhook = trim((string) config('services.n8n.video_studio_preview_webhook', env('N8N_VIDEO_STUDIO_PREVIEW_WEBHOOK', '')));
            if ($webhook === '') {
                $webhook = trim((string) config('services.n8n.video_studio_webhook', env('N8N_VIDEO_STUDIO_WEBHOOK_URL', '')));
                $webhook = preg_replace('~/video-studio-create(?:$|\?)~', '/video-studio-preview', $webhook) ?: $webhook;
            }
            if ($webhook === '') {
                return response()->json(['message' => 'اتصال پیش‌نمایش ورکفلو تنظیم نشده است.'], 422);
            }
            $channel = (string) ($data['channel'] ?? 'instagram');
            $prompt = trim((string) ($data[$channel . '_prompt'] ?? $data['instagram_prompt'] ?? ''));
            $response = Http::retry(3, 300)->timeout(45)->post($webhook, [
                'preview_only' => true,
                'channel' => $channel,
                'product_id' => $product->id,
                'product_name' => (string) $product->name_fa,
                'product_link' => route('app.product', ['product' => $product->route_slug]),
                'hook_guidelines' => (string) ($data['hook_guidelines'] ?? ''),
                'caption_guidelines' => (string) ($data['caption_guidelines'] ?? ''),
                'prompt_profile' => $prompt,
            ]);
            if (!$response->successful()) {
                return response()->json(['message' => 'مدل هوش مصنوعی پاسخ معتبر نداد.'], 502);
            }

            $body = $response->json();
            $raw = data_get($body, 'content.0.text')
                ?? data_get($body, 'text')
                ?? data_get($body, 'output')
                ?? data_get($body, 'response')
                ?? data_get($body, 'result')
                ?? $body;
            if (is_array($raw)) {
                $options = $raw;
            } else {
                $clean = trim((string) $raw);
                $clean = preg_replace('/^```(?:json)?\s*|\s*```$/u', '', $clean) ?: $clean;
                $options = json_decode($clean, true);
            }
            if (!is_array($options)) {
                return response()->json(['message' => 'خروجی مدل قابل تبدیل به پیشنهادهای محتوا نبود.'], 502);
            }

            $normalize = static function (string $key) use ($options): array {
                $value = $options[$key . '_options'] ?? $options[$key] ?? [];
                $value = is_array($value) ? $value : [$value];
                $value = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $value)));
                return array_slice(array_pad($value, 3, $value[0] ?? ''), 0, 3);
            };
            $hooks = $normalize('hook');
            $captions = $normalize('caption');
            $keywords = $normalize('keyword');
            if (count(array_filter(array_merge($hooks, $captions, $keywords))) === 0) {
                return response()->json(['message' => 'پیشنهادی از مدل دریافت نشد.'], 502);
            }

            return response()->json([
                'hook_options' => $hooks,
                'caption_options' => $captions,
                'keyword_options' => $keywords,
                'hook' => $hooks[0] ?? '',
                'caption' => $captions[0] ?? '',
                'keyword' => $keywords[0] ?? '',
                'dm_template' => trim((string) ($options['dm_template'] ?? '')),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->filter()->first() ?: 'اطلاعات پیش‌نمایش کامل نیست.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'ارتباط با مدل هوش مصنوعی ناموفق بود.'], 502);
        }
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

    public function updateHook(Request $request, VideoHookInspiration $hook)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'hook_text' => ['required', 'string', 'max:1000'],
            'tags' => ['nullable', 'string', 'max:500'],
        ]);
        $hook->update($data);

        return back()->with('success', 'هوک ویرایش شد.');
    }

    public function destroyHook(VideoHookInspiration $hook)
    {
        $hook->delete();

        return back()->with('success', 'هوک از کتابخانه حذف شد.');
    }

    public function storeSource(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::in(['music', 'video'])],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'source_file' => ['nullable', 'file', 'mimes:mp3,wav,m4a,ogg,mp4,mov,webm', 'max:102400'],
        ]);
        if ($request->hasFile('source_file')) {
            $data['source_url'] = asset('storage/' . ltrim($request->file('source_file')->store('video-studio/library', 'public'), '/'));
        }
        if (blank($data['source_url'] ?? null)) {
            return back()->withErrors(['source_url' => 'برای منبع، لینک یا فایل انتخاب کنید.']);
        }
        unset($data['source_file']);
        VideoStudioSource::create($data);

        return back()->with('success', 'منبع صدا به کتابخانه اضافه شد.');
    }

    public function destroySource(VideoStudioSource $source)
    {
        $source->delete();

        return back()->with('success', 'منبع از کتابخانه حذف شد.');
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
            'font_family' => ['required', Schema::hasTable('video_studio_fonts')
                ? Rule::exists('video_studio_fonts', 'slug')->where('is_active', true)
                : Rule::in(['B_Yekan'])],
            'hook_text' => ['nullable', 'string', 'max:1000'],
            'caption_text' => ['nullable', 'string', 'max:5000'],
            'prompt_profile' => ['nullable', 'string', 'max:30000'],
            'instagram_prompt' => ['nullable', 'string', 'max:30000'],
            'telegram_prompt' => ['nullable', 'string', 'max:30000'],
            'telegram_buttons_enabled' => ['nullable', 'boolean'],
            'telegram_button_label' => ['nullable', 'array', 'max:8'],
            'telegram_button_label.*' => ['nullable', 'string', 'max:80'],
            'telegram_button_url' => ['nullable', 'array', 'max:8'],
            'telegram_button_url.*' => ['nullable', 'url', 'max:2048'],
            'telegram_button_style' => ['nullable', 'array', 'max:8'],
            'telegram_button_style.*' => ['nullable', Rule::in(['primary', 'success', 'danger'])],
            'prompt_file' => ['nullable', 'file', 'mimes:txt,md', 'max:512'],
            'keyword' => ['nullable', 'string', 'max:80'],
            'dm_template' => ['nullable', 'string', 'max:5000'],
            'build_now' => ['nullable', 'boolean'],
            'source_library_id' => ['nullable', 'integer', 'exists:video_studio_sources,id'],
            'preview_hook' => ['nullable', 'string', 'max:1000'],
            'preview_caption' => ['nullable', 'string', 'max:5000'],
            'preview_keyword' => ['nullable', 'string', 'max:80'],
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
        if (blank($data['instagram_prompt'] ?? null) && filled($data['prompt_profile'] ?? null)) {
            $data['instagram_prompt'] = $data['prompt_profile'];
        }
        $telegramButtons = $this->normalizeTelegramButtons($request);
        unset($data['telegram_buttons_enabled'], $data['telegram_button_label'], $data['telegram_button_url'], $data['telegram_button_style']);
        if (!empty($data['source_library_id'])) {
            $librarySource = VideoStudioSource::query()->where('is_active', true)->findOrFail((int) $data['source_library_id']);
            $data['source_mode'] = $librarySource->type;
            $data['source_url'] = $librarySource->source_url;
        }
        // در حالت خودکار، منبعی از کتابخانه که کمترین مصرف را دارد انتخاب می‌کنیم
        // تا هر سفارش از یک منبع قبلی تکراری استفاده نکند و گردش منابع قابل ردیابی بماند.
        if (($data['source_mode'] ?? null) === 'auto'
            && empty($data['source_library_id'])
            && Schema::hasTable('video_studio_sources')) {
            $librarySource = VideoStudioSource::query()
                ->where('is_active', true)
                ->orderBy('used_count')
                ->orderBy('id')
                ->first();
            if ($librarySource) {
                $data['source_library_id'] = $librarySource->id;
                $data['source_mode'] = $librarySource->type;
                $data['source_url'] = $librarySource->source_url;
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
        if (filled($data['preview_hook'] ?? null)) {
            $data['hook_text'] = trim((string) $data['preview_hook']);
            $autoHook = false;
        }
        if (filled($data['preview_caption'] ?? null)) {
            $data['caption_text'] = trim((string) $data['preview_caption']);
            $autoCaption = false;
        }
        if (filled($data['preview_keyword'] ?? null)) {
            $data['keyword'] = trim((string) $data['preview_keyword']);
            $autoKeyword = false;
        }
        $buildNow = $request->boolean('build_now');
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
                'font_family' => (string) ($data['font_family'] ?? 'B_Yekan'),
                'prompt_profile' => (string) ($data['prompt_profile'] ?? ''),
                'instagram_prompt' => (string) ($data['instagram_prompt'] ?? ''),
                'telegram_prompt' => (string) ($data['telegram_prompt'] ?? ''),
                'telegram_buttons' => $telegramButtons,
                'source_fingerprint' => $sourceFingerprint,
                'build_now' => $buildNow,
                'source_library_id' => (int) ($data['source_library_id'] ?? 0) ?: null,
                'preview_selected' => filled($data['preview_hook'] ?? null) || filled($data['preview_caption'] ?? null) || filled($data['preview_keyword'] ?? null),
            ],
        ]));

        if ($buildNow) {
            $this->dispatchJobToWorkflow($job);
        }

        return redirect()->route('admin.products.dashboard', ['product_id' => $job->product_id])
            ->with('success', $buildNow
                ? ($job->status === 'processing' ? 'ساخت ویدیو شروع شد و در صف پردازش قرار گرفت.' : 'سفارش در صف ساخت ثبت شد. اتصال اجرای خودکار هنوز تنظیم نشده است.')
                : 'تنظیمات در لیست ساخت ذخیره شد و هنوز ویدیو ساخته نمی‌شود.');
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

    private function normalizeTelegramButtons(Request $request): array
    {
        if (!$request->boolean('telegram_buttons_enabled')) {
            return [];
        }

        $labels = $request->input('telegram_button_label', []);
        $urls = $request->input('telegram_button_url', []);
        $styles = $request->input('telegram_button_style', []);
        $buttons = [];
        foreach ((array) $labels as $index => $label) {
            $label = trim((string) $label);
            $url = trim((string) ($urls[$index] ?? ''));
            if ($label === '' || $url === '') {
                continue;
            }
            $buttons[] = [
                'label' => $label,
                'url' => $url,
                'style' => in_array(($styles[$index] ?? 'primary'), ['primary', 'success', 'danger'], true)
                    ? (string) $styles[$index]
                    : 'primary',
            ];
        }

        return array_slice($buttons, 0, 8);
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
        $channel = (string) ($payload['channel'] ?? 'instagram');
        $channelPrompt = trim((string) ($payload[$channel . '_prompt'] ?? ''));
        if ($channelPrompt !== '') {
            $promptProfile = $channelPrompt;
        }
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
                'font_family' => (string) ($job->payload['font_family'] ?? ''),
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
                if (!empty($payload['source_library_id'])) {
                    VideoStudioSource::query()->whereKey((int) $payload['source_library_id'])->update([
                        'used_count' => DB::raw('used_count + 1'),
                        'last_used_at' => now(),
                    ]);
                }
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
