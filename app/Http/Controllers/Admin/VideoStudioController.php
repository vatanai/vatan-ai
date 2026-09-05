<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneratedVideo;
use App\Models\AiModel;
use App\Models\FinanceExchangeRate;
use App\Models\Product;
use App\Models\ProductTestRun;
use App\Models\VideoHookInspiration;
use App\Models\VideoStudioJob;
use App\Models\VideoStudioSetting;
use App\Models\VideoStudioSource;
use App\Models\VideoStudioFont;
use App\Models\VideoStudioHookColor;
use App\Models\VideoStudioHookColorPreference;
use App\Models\VideoStudioPreset;
use App\Models\VideoStudioSocialPrompt;
use App\Support\Jalali;
use App\Services\StudioCostService;
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
    public function index(Request $request, string $view = 'admin.video-studio.index')
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
        // بعد از ثبت سفارش، سازنده باید برای سفارش بعدی خالی شود تا تنظیمات سفارش قبلی
        // ناخواسته دوباره ارسال نشود. انتخاب محصول فقط وقتی از URL خوانده می‌شود که
        // صفحه در حالت تازه‌سازی سازنده نباشد.
        $selectedProductId = $request->boolean('fresh')
            ? null
            : ($request->integer('product_id') ?: null);
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
        if ($request->boolean('fresh')) {
            // صفحهٔ سازنده پس از ثبت سفارش باید برای سفارش بعدی کاملاً خنثی باشد؛
            // مقادیر پرامپت مادر باقی می‌مانند اما محصول و منبع قبلی نه.
            $settings->product_id = null;
            $settings->source_mode = 'auto';
            $settings->source_url = null;
            $settings->hook_text = null;
            $settings->caption_text = null;
            $settings->telegram_caption_text = null;
            $settings->keyword = null;
            $settings->dm_template = null;
            $settings->aspect_ratio = '9:16';
        }
        $hookInspirations = Schema::hasTable('video_hook_inspirations')
            ? VideoHookInspiration::query()->with('product')->where('is_active', true)->latest()->limit(12)->get()
            : collect();
        $sources = Schema::hasTable('video_studio_sources')
            ? VideoStudioSource::query()->where('is_active', true)->latest()->get()
            : collect();
        $presets = Schema::hasTable('video_studio_presets')
            ? VideoStudioPreset::query()->where(function ($query): void {
                $query->whereNull('admin_id')->orWhere('admin_id', auth('admin')->id());
            })->orderBy('name')->get(['id', 'name', 'settings'])
            : collect();
        $socialPrompts = Schema::hasTable('video_studio_social_prompts')
            ? VideoStudioSocialPrompt::query()->where('admin_id', auth('admin')->id())->pluck('prompt', 'platform')->all()
            : [];
        $socialPrompts['instagram'] = (string) ($socialPrompts['instagram'] ?? $settings->instagram_prompt ?? '');
        $socialPrompts['telegram'] = (string) ($socialPrompts['telegram'] ?? $settings->telegram_prompt ?? '');
        $socialPrompts['hook'] = (string) ($socialPrompts['hook'] ?? $settings->hook_guidelines ?? '');
        $hookColors = [
            'background' => $this->hookColorOptions('background'),
            'text' => $this->hookColorOptions('text'),
        ];
        $fallbackFonts = collect([
            new VideoStudioFont(['name' => 'یکان', 'slug' => 'B_Yekan', 'file_path' => 'fonts/B_Yekan.ttf', 'is_default' => true]),
            new VideoStudioFont(['name' => 'ابر', 'slug' => 'Abar', 'file_path' => 'fonts/video/AbarMid-Regular.ttf', 'is_default' => false]),
            new VideoStudioFont(['name' => 'ایران‌سنس', 'slug' => 'IRANSansX', 'file_path' => 'fonts/IRANSansXFaNum-RegularD4.ttf', 'is_default' => false]),
            new VideoStudioFont(['name' => 'پیدا', 'slug' => 'Peyda', 'file_path' => 'fonts/video/Peyda-Medium.ttf', 'is_default' => false]),
            new VideoStudioFont(['name' => 'دوران', 'slug' => 'Doran', 'file_path' => 'fonts/video/Doran-Regular.ttf', 'is_default' => false]),
            new VideoStudioFont(['name' => 'مدام', 'slug' => 'Modam', 'file_path' => 'fonts/video/Modam-Medium.ttf', 'is_default' => false]),
            new VideoStudioFont(['name' => 'یکان‌بخ', 'slug' => 'YekanBakh', 'file_path' => 'fonts/video/YekanBakh-Medium.ttf', 'is_default' => false]),
        ]);
        $storedFonts = Schema::hasTable('video_studio_fonts')
            ? VideoStudioFont::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get()
            : collect();
        // حتی اگر دیتابیس تولید فونت‌ها را ناقص داشته باشد، گزینه‌های ارسالی مدیر حذف نمی‌شوند.
        $fonts = $fallbackFonts->concat($storedFonts)->unique('slug')->values();
        $fontWeightAssets = $this->videoStudioFontWeightAssets($fonts);
        if (Schema::hasTable('video_studio_jobs')) {
            // اجرای عادی پایپ‌لاین کوتاه است؛ اگر callback برنگردد، سفارش نباید
            // برای همیشه روی «در حال ساخت» بماند و باید دلیل قابل‌فهمی برای ساخت مجدد داشته باشد.
            VideoStudioJob::query()
                ->where('status', 'processing')
                ->where('updated_at', '<', now()->subMinutes(15))
                ->whereNull('video_url')
                ->get()
                ->each(function (VideoStudioJob $staleJob): void {
                    $staleJob->update([
                        'status' => 'failed',
                        'error_message' => $staleJob->error_message ?: 'پردازش در زمان مقرر پاسخ نداد؛ منبع و تنظیمات را بررسی کنید و ساخت مجدد را بزنید.',
                    ]);
                });
        }
        $jobs = Schema::hasTable('video_studio_jobs')
            ? VideoStudioJob::query()->with('product')->latest()->limit(20)->get()
            : collect();
        $estimatedCosts = $jobs->mapWithKeys(function (VideoStudioJob $job): array {
            $saved = data_get($job->payload, 'estimated_cost', []);
            return [$job->id => is_array($saved) && $saved !== []
                ? $saved
                : $this->estimateVideoCost((int) $job->product_id, (string) $job->aspect_ratio)];
        });
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

        return response()->view($view, [
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
            'presets' => $presets,
            'socialPrompts' => $socialPrompts,
            'hookColors' => $hookColors,
            'fonts' => $fonts,
            'fontWeightAssets' => $fontWeightAssets,
            'jobs' => $jobs,
            'estimatedCosts' => $estimatedCosts,
            'completedVideoCounts' => $completedVideoCounts,
            'pendingVideoCounts' => $pendingVideoCounts,
            'producedProducts' => $producedProducts,
            'daily' => $daily,
            'dataSources' => [
                'products' => true,
                'generated_videos' => $hasGeneratedVideos,
                'product_test_runs' => $hasProductRuns,
            ],
            'experimentalPage' => $view === 'admin.video-studio.experimental',
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * نسخهٔ تکثیرشدهٔ استودیو برای اجرای فازهای جدید، بدون تغییر رفتار صفحهٔ فعلی.
     */
    public function experimental(Request $request)
    {
        return $this->index($request, 'admin.video-studio.experimental');
    }

    public function storePreset(Request $request)
    {
        abort_unless(Schema::hasTable('video_studio_presets'), 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'settings' => ['required', 'array', 'max:80'],
        ]);
        $preset = VideoStudioPreset::query()->updateOrCreate(
            ['admin_id' => auth('admin')->id(), 'name' => trim($data['name'])],
            ['settings' => $data['settings']],
        );

        return response()->json(['id' => $preset->id, 'name' => $preset->name, 'settings' => $preset->settings]);
    }

    public function renamePreset(Request $request, VideoStudioPreset $preset)
    {
        abort_unless((int) $preset->admin_id === (int) auth('admin')->id(), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $preset->update(['name' => trim($data['name'])]);

        return response()->json(['id' => $preset->id, 'name' => $preset->name, 'settings' => $preset->settings]);
    }

    public function destroyPreset(VideoStudioPreset $preset)
    {
        abort_unless((int) $preset->admin_id === (int) auth('admin')->id(), 403);
        $preset->delete();

        return response()->json(['deleted' => true]);
    }

    public function storeSocialPrompts(Request $request)
    {
        abort_unless(Schema::hasTable('video_studio_social_prompts'), 404);
        $data = $request->validate([
            'prompts' => ['required', 'array', 'max:8'],
            'prompts.instagram' => ['nullable', 'string', 'max:30000'],
            'prompts.telegram' => ['nullable', 'string', 'max:30000'],
            'prompts.youtube' => ['nullable', 'string', 'max:30000'],
            'prompts.aparat' => ['nullable', 'string', 'max:30000'],
            'prompts.linkedin' => ['nullable', 'string', 'max:30000'],
            'prompts.hook' => ['nullable', 'string', 'max:30000'],
        ]);
        foreach (['instagram', 'telegram', 'youtube', 'aparat', 'linkedin', 'hook'] as $platform) {
            if (!array_key_exists($platform, $data['prompts'])) {
                continue;
            }
            VideoStudioSocialPrompt::query()->updateOrCreate(
                ['admin_id' => auth('admin')->id(), 'platform' => $platform],
                ['prompt' => trim((string) data_get($data, "prompts.{$platform}", ''))],
            );
        }

        return response()->json(['saved' => true, 'prompts' => VideoStudioSocialPrompt::query()->where('admin_id', auth('admin')->id())->pluck('prompt', 'platform')->all()]);
    }

    public function storeHookColor(Request $request)
    {
        abort_unless(Schema::hasTable('video_studio_hook_colors'), 404);

        $data = $request->validate([
            'target' => ['required', Rule::in(['background', 'text'])],
            'name' => ['nullable', 'string', 'max:80'],
            'color_value' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
        $color = VideoStudioHookColor::query()->updateOrCreate(
            [
                'admin_id' => auth('admin')->id(),
                'target' => $data['target'],
                'color_value' => strtoupper($data['color_value']),
            ],
            ['name' => trim((string) ($data['name'] ?? '')) ?: 'رنگ سفارشی'],
        );

        return response()->json([
            'saved' => true,
            'color' => $this->hookColorPayload($color),
        ]);
    }

    public function destroyHookColor(VideoStudioHookColor $color)
    {
        abort_unless((int) $color->admin_id === (int) auth('admin')->id(), 403);
        $color->delete();

        return response()->json(['deleted' => true]);
    }

    public function destroyDefaultHookColor(Request $request, string $target, string $colorKey)
    {
        abort_unless(in_array($target, ['background', 'text'], true), 404);
        abort_unless(collect($this->defaultHookColors($target))->contains('key', $colorKey), 404);
        abort_unless(Schema::hasTable('video_studio_hook_color_preferences'), 404);

        VideoStudioHookColorPreference::query()->updateOrCreate(
            [
                'admin_id' => auth('admin')->id(),
                'target' => $target,
                'color_key' => $colorKey,
            ],
            ['is_hidden' => true],
        );

        return response()->json(['deleted' => true]);
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
            'telegram_caption_text' => ['nullable', 'string', 'max:5000'],
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
            'telegram_button_width' => ['nullable', 'array', 'max:8'],
            'telegram_button_width.*' => ['nullable', Rule::in(['full', 'half'])],
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
        unset($data['telegram_buttons_enabled'], $data['telegram_button_label'], $data['telegram_button_url'], $data['telegram_button_style'], $data['telegram_button_width']);
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
                'youtube_prompt' => ['nullable', 'string', 'max:30000'],
                'aparat_prompt' => ['nullable', 'string', 'max:30000'],
                'linkedin_prompt' => ['nullable', 'string', 'max:30000'],
                'channel' => ['nullable', Rule::in(['instagram', 'telegram', 'youtube', 'aparat', 'linkedin'])],
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
            if ($prompt === '' && Schema::hasTable('video_studio_social_prompts')) {
                $prompt = trim((string) VideoStudioSocialPrompt::query()
                    ->where('admin_id', auth('admin')->id())
                    ->where('platform', $channel)
                    ->value('prompt'));
            }
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
            $ctas = $normalize('cta');
            if (count(array_filter($ctas)) === 0) {
                $ctas = array_fill(0, 3, 'برای دیدن جزئیات این محصول، کپشن را بخوان و کلمهٔ کلیدی را کامنت کن.');
            }
            if (count(array_filter(array_merge($hooks, $captions, $keywords))) === 0) {
                return response()->json(['message' => 'پیشنهادی از مدل دریافت نشد.'], 502);
            }

            return response()->json([
                'hook_options' => $hooks,
                'caption_options' => $captions,
                'keyword_options' => $keywords,
                'cta_options' => $ctas,
                'hook' => $hooks[0] ?? '',
                'caption' => $captions[0] ?? '',
                'keyword' => $keywords[0] ?? '',
                'cta' => $ctas[0] ?? '',
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
            'selected_image_order' => ['nullable', 'array', 'max:10'],
            'selected_image_order.*' => ['string', 'max:2048'],
            'aspect_ratio' => ['required', Rule::in(['9:16', '1:1', '4:5', '16:9'])],
            'instagram_enabled' => ['nullable', 'boolean'],
            'telegram_enabled' => ['nullable', 'boolean'],
            'youtube_enabled' => ['nullable', 'boolean'],
            'aparat_enabled' => ['nullable', 'boolean'],
            'linkedin_enabled' => ['nullable', 'boolean'],
            'telegram_send_video' => ['nullable', 'boolean'],
            'telegram_send_images' => ['nullable', 'boolean'],
            'instagram_send_video' => ['nullable', 'boolean'],
            'instagram_send_images' => ['nullable', 'boolean'],
            'youtube_send_video' => ['nullable', 'boolean'],
            'youtube_send_images' => ['nullable', 'boolean'],
            'aparat_send_video' => ['nullable', 'boolean'],
            'aparat_send_images' => ['nullable', 'boolean'],
            'linkedin_send_video' => ['nullable', 'boolean'],
            'linkedin_send_images' => ['nullable', 'boolean'],
            'cta_enabled' => ['nullable', 'boolean'],
            'cta_text' => ['nullable', 'string', 'max:1000'],
            'cta_background' => ['nullable', 'string', 'max:60', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! $this->isKnownHookColor((string) $value, 'background')) {
                    $fail('رنگ پس‌زمینه CTA معتبر نیست.');
                }
            }],
            'cta_text_color' => ['nullable', 'string', 'max:60', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! $this->isKnownHookColor((string) $value, 'text')) {
                    $fail('رنگ متن CTA معتبر نیست.');
                }
            }],
            'cta_font_size' => ['nullable', 'numeric', 'between:20,72'],
            'cta_font_weight' => ['nullable', 'integer', 'between:1,5'],
            'cta_scale' => ['nullable', 'numeric', 'between:0.7,1.5'],
            'cta_vertical_offset' => ['nullable', 'numeric', 'between:-45,45'],
            'cta_guidelines' => ['nullable', 'string', 'max:5000'],
            'cta_duration' => ['nullable', 'numeric', 'between:0.1,5'],
            'cta_duration_mode' => ['nullable', Rule::in(['manual', 'auto'])],
            'hook_background' => ['nullable', 'string', 'max:60', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! $this->isKnownHookColor((string) $value, 'background')) {
                    $fail('رنگ پس‌زمینه هوک معتبر نیست.');
                }
            }],
            'hook_text_color' => ['nullable', 'string', 'max:60', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! $this->isKnownHookColor((string) $value, 'text')) {
                    $fail('رنگ متن هوک معتبر نیست.');
                }
            }],
            'hook_font_size' => ['nullable', 'numeric', 'between:20,72'],
            'hook_font_weight' => ['nullable', 'integer', 'between:1,5'],
            'hook_scale' => ['nullable', 'numeric', 'between:0.7,1.5'],
            'hook_vertical_offset' => ['nullable', 'numeric', 'between:-45,45'],
            'hook_duration' => ['nullable', 'numeric', 'between:0.1,5'],
            'hook_duration_mode' => ['nullable', Rule::in(['manual', 'auto'])],
            'hook_guidelines' => ['nullable', 'string', 'max:5000'],
            'hook_position' => ['nullable', Rule::in(['top', 'center', 'bottom', 'side'])],
            'cta_position' => ['nullable', Rule::in(['top', 'center', 'bottom', 'side'])],
            'transition' => ['nullable', Rule::in(['cut', 'fade', 'blur', 'slide'])],
            'transition_duration' => ['nullable', 'numeric', 'between:0.2,1.5'],
            'text_command' => ['nullable', 'string', 'max:5000'],
            'font_family' => ['required', Schema::hasTable('video_studio_fonts')
                ? Rule::exists('video_studio_fonts', 'slug')->where('is_active', true)
                : Rule::in(['B_Yekan'])],
            'hook_text' => ['nullable', 'string', 'max:1000'],
            'caption_text' => ['nullable', 'string', 'max:5000'],
            'telegram_caption_text' => ['nullable', 'string', 'max:5000'],
            'prompt_profile' => ['nullable', 'string', 'max:30000'],
            'instagram_prompt' => ['nullable', 'string', 'max:30000'],
            'telegram_prompt' => ['nullable', 'string', 'max:30000'],
            'youtube_prompt' => ['nullable', 'string', 'max:30000'],
            'aparat_prompt' => ['nullable', 'string', 'max:30000'],
            'linkedin_prompt' => ['nullable', 'string', 'max:30000'],
            'channel' => ['nullable', Rule::in(['instagram', 'telegram', 'youtube', 'aparat', 'linkedin'])],
            'telegram_buttons_enabled' => ['nullable', 'boolean'],
            'telegram_button_label' => ['nullable', 'array', 'max:8'],
            'telegram_button_label.*' => ['nullable', 'string', 'max:80'],
            'telegram_button_url' => ['nullable', 'array', 'max:8'],
            'telegram_button_url.*' => ['nullable', 'url', 'max:2048'],
            'telegram_button_style' => ['nullable', 'array', 'max:8'],
            'telegram_button_style.*' => ['nullable', Rule::in(['primary', 'success', 'danger'])],
            'telegram_button_width' => ['nullable', 'array', 'max:8'],
            'telegram_button_width.*' => ['nullable', Rule::in(['full', 'half'])],
            'prompt_file' => ['nullable', 'file', 'mimes:txt,md', 'max:512'],
            'keyword' => ['nullable', 'string', 'max:80'],
            'dm_template' => ['nullable', 'string', 'max:5000'],
            'build_now' => ['nullable', 'boolean'],
            'source_library_id' => ['nullable', 'integer', 'exists:video_studio_sources,id'],
            'preview_hook' => ['nullable', 'string', 'max:1000'],
            'preview_caption' => ['nullable', 'string', 'max:5000'],
            'preview_keyword' => ['nullable', 'string', 'max:80'],
            'parent_job_id' => ['nullable', 'integer', 'exists:video_studio_jobs,id'],
            'version' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        // ترتیب انتخاب مدیر روی تصاویر، ترتیب نمایش پلان‌ها بعد از هوک است؛ این ترتیب
        // از ترتیب اولیهٔ تصاویر محصول جدا نگه‌داری می‌شود تا دقیقاً به ورکفلو برسد.
        $data['selected_images'] = collect($data['selected_image_order'] ?? $data['selected_images'] ?? [])
            ->map(static fn ($image): string => trim((string) $image))
            ->filter()
            ->unique()
            ->take(10)
            ->values()
            ->all();
        unset($data['selected_image_order']);

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
        $hookBackground = $this->resolveHookColor((string) ($data['hook_background'] ?? 'primary'), 'background');
        $hookTextColor = $this->resolveHookColor((string) ($data['hook_text_color'] ?? 'light'), 'text');
        $data['hook_background'] = $hookBackground['key'];
        $data['hook_background_color'] = $hookBackground['render_value'];
        $data['hook_text_color'] = $hookTextColor['key'];
        $data['hook_text_color_value'] = $hookTextColor['render_value'];
        $ctaBackground = $this->resolveHookColor((string) ($data['cta_background'] ?? 'primary'), 'background');
        $ctaTextColor = $this->resolveHookColor((string) ($data['cta_text_color'] ?? 'light'), 'text');
        $data['cta_background'] = $ctaBackground['key'];
        $data['cta_background_color'] = $ctaBackground['render_value'];
        $data['cta_text_color'] = $ctaTextColor['key'];
        $data['cta_text_color_value'] = $ctaTextColor['render_value'];
        if (Schema::hasTable('video_studio_social_prompts')) {
            $savedPrompts = VideoStudioSocialPrompt::query()
                ->where('admin_id', auth('admin')->id())
                ->pluck('prompt', 'platform');
            foreach (['instagram', 'telegram', 'youtube', 'aparat', 'linkedin'] as $platform) {
                $key = $platform . '_prompt';
                if (blank($data[$key] ?? null) && filled($savedPrompts[$platform] ?? null)) {
                    $data[$key] = (string) $savedPrompts[$platform];
                }
            }
            if (blank($data['hook_guidelines'] ?? null) && filled($savedPrompts['hook'] ?? null)) {
                $data['hook_guidelines'] = (string) $savedPrompts['hook'];
            }
        }
        $telegramButtons = $this->normalizeTelegramButtons($request);
        unset($data['telegram_buttons_enabled'], $data['telegram_button_label'], $data['telegram_button_url'], $data['telegram_button_style'], $data['telegram_button_width']);
        // مقدار لینکِ پنهان فرم نباید باعث استفادهٔ دوباره از منبع سفارش قبلی شود.
        // در حالت خودکار فقط کتابخانهٔ فعال و معتبر مجاز است.
        if (($data['source_mode'] ?? null) === 'auto' && empty($data['source_library_id'])) {
            $data['source_url'] = null;
        }
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
                ->whereNotNull('source_url')
                ->where('source_url', '<>', '')
                ->orderBy('used_count')
                ->orderBy('id')
                ->first();
            if ($librarySource) {
                $data['source_library_id'] = $librarySource->id;
                $data['source_mode'] = $librarySource->type;
                $data['source_url'] = $librarySource->source_url;
            }
        }
        $sourceError = null;
        if (blank($data['source_url'])) {
            $sourceError = 'منبع صوت یا ویدیو انتخاب نشده است. از کتابخانه یک منبع معتبر انتخاب کنید یا فایل/لینک جدید بدهید.';
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
        $parentJobId = (int) ($data['parent_job_id'] ?? 0) ?: null;
        $version = max(1, min(99, (int) ($data['version'] ?? 1)));
        unset($data['parent_job_id'], $data['version']);
        $instagramEnabled = $request->has('instagram_enabled') ? $request->boolean('instagram_enabled') : true;
        $telegramEnabled = $request->has('telegram_enabled') ? $request->boolean('telegram_enabled') : true;
        $youtubeEnabled = $request->boolean('youtube_enabled');
        $aparatEnabled = $request->boolean('aparat_enabled');
        $linkedinEnabled = $request->boolean('linkedin_enabled');
        $platformError = (!$instagramEnabled && !$telegramEnabled && !$youtubeEnabled && !$aparatEnabled && !$linkedinEnabled)
            ? 'حداقل یکی از خروجی‌های شبکه‌های اجتماعی باید روشن باشد.'
            : null;
        if ($autoHook) $data['hook_text'] = null;
        if ($autoCaption) $data['caption_text'] = null;
        if ($autoKeyword) $data['keyword'] = null;
        $sourceFingerprint = hash('sha256', json_encode([
            'product_id' => (int) $data['product_id'],
            'source_mode' => (string) $data['source_mode'],
            'source_url' => (string) ($data['source_url'] ?? ''),
            'selected_images' => array_values($data['selected_images'] ?? []),
            'aspect_ratio' => (string) $data['aspect_ratio'],
            'hook_duration' => (float) ($data['hook_duration'] ?? 2),
            'hook_duration_mode' => (string) ($data['hook_duration_mode'] ?? 'manual'),
            'cta_duration' => (float) ($data['cta_duration'] ?? 2),
            'cta_duration_mode' => (string) ($data['cta_duration_mode'] ?? 'manual'),
            'cta_enabled' => (bool) ($data['cta_enabled'] ?? true),
            'cta_text' => (string) ($data['cta_text'] ?? ''),
            'cta_background' => (string) ($data['cta_background'] ?? 'primary'),
            'cta_text_color' => (string) ($data['cta_text_color'] ?? 'light'),
            'cta_font_size' => (float) ($data['cta_font_size'] ?? 36),
            'cta_font_weight' => (int) ($data['cta_font_weight'] ?? 3),
            'cta_scale' => (float) ($data['cta_scale'] ?? 1),
            'cta_vertical_offset' => (float) ($data['cta_vertical_offset'] ?? 0),
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
            // سفارش ساخت بدون منبع نباید در حالت «در حال ساخت» معلق بماند؛
            // همان ابتدا به‌عنوان ناموفق ثبت می‌شود تا دلیل آن در صف دیده شود.
            'status' => $buildNow && ($sourceError || $platformError) ? 'failed' : 'queued',
            'error_message' => $buildNow ? ($sourceError ?: $platformError) : null,
            'payload' => [
                'created_from' => 'video_studio_dashboard',
                'auto_generate_hook' => $autoHook,
                'auto_generate_caption' => $autoCaption,
                'auto_generate_keyword' => $autoKeyword,
                'hook_guidelines' => (string) ($data['hook_guidelines'] ?? ''),
                'caption_guidelines' => (string) $request->input('caption_guidelines', ''),
                'font_family' => (string) ($data['font_family'] ?? 'B_Yekan'),
                'prompt_profile' => (string) ($data['prompt_profile'] ?? ''),
                'instagram_prompt' => (string) ($data['instagram_prompt'] ?? ''),
                'telegram_prompt' => (string) ($data['telegram_prompt'] ?? ''),
                'youtube_prompt' => (string) ($data['youtube_prompt'] ?? ''),
                'aparat_prompt' => (string) ($data['aparat_prompt'] ?? ''),
                'linkedin_prompt' => (string) ($data['linkedin_prompt'] ?? ''),
                'telegram_caption_text' => (string) ($data['telegram_caption_text'] ?? ''),
                'youtube_caption_text' => (string) ($data['youtube_caption_text'] ?? ''),
                'aparat_caption_text' => (string) ($data['aparat_caption_text'] ?? ''),
                'linkedin_caption_text' => (string) ($data['linkedin_caption_text'] ?? ''),
                'telegram_buttons' => $telegramButtons,
                'channel' => (string) ($data['channel'] ?? 'instagram'),
                'source_fingerprint' => $sourceFingerprint,
                'build_now' => $buildNow,
                'source_library_id' => (int) ($data['source_library_id'] ?? 0) ?: null,
                'preview_selected' => filled($data['preview_hook'] ?? null) || filled($data['preview_caption'] ?? null) || filled($data['preview_keyword'] ?? null),
                'instagram_enabled' => $instagramEnabled,
                'telegram_enabled' => $telegramEnabled,
                'youtube_enabled' => $youtubeEnabled,
                'aparat_enabled' => $aparatEnabled,
                'linkedin_enabled' => $linkedinEnabled,
                'telegram_send_video' => $request->boolean('telegram_send_video'),
                'telegram_send_images' => $request->boolean('telegram_send_images'),
                'instagram_send_video' => $request->boolean('instagram_send_video'),
                'instagram_send_images' => $request->boolean('instagram_send_images'),
                'youtube_send_video' => $request->boolean('youtube_send_video'),
                'youtube_send_images' => $request->boolean('youtube_send_images'),
                'aparat_send_video' => $request->boolean('aparat_send_video'),
                'aparat_send_images' => $request->boolean('aparat_send_images'),
                'linkedin_send_video' => $request->boolean('linkedin_send_video'),
                'linkedin_send_images' => $request->boolean('linkedin_send_images'),
                'cta_enabled' => $request->has('cta_enabled') ? $request->boolean('cta_enabled') : true,
                'cta_text' => (string) ($data['cta_text'] ?? ''),
                'cta_background' => (string) ($data['cta_background'] ?? 'primary'),
                'cta_background_color' => (string) ($data['cta_background_color'] ?? '#16594F'),
                'cta_text_color' => (string) ($data['cta_text_color'] ?? 'light'),
                'cta_text_color_value' => (string) ($data['cta_text_color_value'] ?? '#FFFFFF'),
                'cta_font_size' => (float) ($data['cta_font_size'] ?? 36),
                'cta_font_weight' => (int) ($data['cta_font_weight'] ?? 3),
                'cta_scale' => (float) ($data['cta_scale'] ?? 1),
                'cta_vertical_offset' => (float) ($data['cta_vertical_offset'] ?? 0),
                'cta_guidelines' => (string) ($data['cta_guidelines'] ?? ''),
                'cta_duration' => (float) ($data['cta_duration'] ?? 2),
                'cta_duration_mode' => (string) ($data['cta_duration_mode'] ?? 'manual'),
                'hook_background' => (string) ($data['hook_background'] ?? 'primary'),
                'hook_background_color' => (string) ($data['hook_background_color'] ?? '#16594F'),
                'hook_text_color' => (string) ($data['hook_text_color'] ?? 'light'),
                'hook_text_color_value' => (string) ($data['hook_text_color_value'] ?? '#FFFFFF'),
                'hook_font_size' => (float) ($data['hook_font_size'] ?? 36),
                'hook_font_weight' => (int) ($data['hook_font_weight'] ?? 3),
                'hook_scale' => (float) ($data['hook_scale'] ?? 1),
                'hook_vertical_offset' => (float) ($data['hook_vertical_offset'] ?? 0),
                'hook_duration' => (float) ($data['hook_duration'] ?? 2),
                'hook_duration_mode' => (string) ($data['hook_duration_mode'] ?? 'manual'),
                'image_sequence' => collect($data['selected_images'] ?? [])
                    ->values()
                    ->map(static fn (string $url, int $index): array => ['url' => $url, 'order' => $index + 1])
                    ->all(),
                'hook_position' => (string) ($data['hook_position'] ?? 'center'),
                'cta_position' => (string) ($data['cta_position'] ?? 'bottom'),
                'transition' => (string) ($data['transition'] ?? 'cut'),
                'transition_duration' => (float) ($data['transition_duration'] ?? 0.5),
                'text_command' => trim((string) ($data['text_command'] ?? '')),
                'parent_job_id' => $parentJobId,
                'version' => $version,
                'render_config' => [
                    'font_family' => (string) ($data['font_family'] ?? 'B_Yekan'),
                    'hook_background' => (string) ($data['hook_background'] ?? 'primary'),
                    'hook_background_color' => (string) ($data['hook_background_color'] ?? '#16594F'),
                    'hook_text_color' => (string) ($data['hook_text_color'] ?? 'light'),
                    'hook_text_color_value' => (string) ($data['hook_text_color_value'] ?? '#FFFFFF'),
                    'hook_font_size' => (float) ($data['hook_font_size'] ?? 36),
                    'hook_font_weight' => (int) ($data['hook_font_weight'] ?? 3),
                    'hook_scale' => (float) ($data['hook_scale'] ?? 1),
                    'hook_vertical_offset' => (float) ($data['hook_vertical_offset'] ?? 0),
                    'hook_duration' => (float) ($data['hook_duration'] ?? 2),
                    'hook_duration_mode' => (string) ($data['hook_duration_mode'] ?? 'manual'),
                    'hook_position' => (string) ($data['hook_position'] ?? 'center'),
                    'cta_position' => (string) ($data['cta_position'] ?? 'bottom'),
                    'cta_enabled' => $request->has('cta_enabled') ? $request->boolean('cta_enabled') : true,
                    'cta_text' => (string) ($data['cta_text'] ?? ''),
                    'cta_background' => (string) ($data['cta_background'] ?? 'primary'),
                    'cta_background_color' => (string) ($data['cta_background_color'] ?? '#16594F'),
                    'cta_text_color' => (string) ($data['cta_text_color'] ?? 'light'),
                    'cta_text_color_value' => (string) ($data['cta_text_color_value'] ?? '#FFFFFF'),
                    'cta_font_size' => (float) ($data['cta_font_size'] ?? 36),
                    'cta_font_weight' => (int) ($data['cta_font_weight'] ?? 3),
                    'cta_scale' => (float) ($data['cta_scale'] ?? 1),
                    'cta_vertical_offset' => (float) ($data['cta_vertical_offset'] ?? 0),
                    'cta_guidelines' => (string) ($data['cta_guidelines'] ?? ''),
                    'cta_duration' => (float) ($data['cta_duration'] ?? 2),
                    'cta_duration_mode' => (string) ($data['cta_duration_mode'] ?? 'manual'),
                    'transition' => (string) ($data['transition'] ?? 'cut'),
                    'transition_duration' => (float) ($data['transition_duration'] ?? 0.5),
                ],
                'estimated_cost' => $this->estimateVideoCost((int) $data['product_id'], (string) $data['aspect_ratio']),
            ],
        ]));

        if ($buildNow && !$sourceError && !$platformError) {
            $this->dispatchJobToWorkflow($job);
        }

        return redirect()->route('admin.products.dashboard', ['fresh' => 1])
            ->with('success', $buildNow
                ? (($sourceError || $platformError) ? 'سفارش ثبت شد اما پیش از ساخت ناموفق علامت خورد؛ تنظیمات را اصلاح و ساخت مجدد را بزنید.' : ($job->status === 'processing' ? 'ساخت ویدیو شروع شد و در صف پردازش قرار گرفت.' : 'سفارش در صف ساخت ثبت شد.'))
                : 'تنظیمات در لیست ساخت ذخیره شد و هنوز ویدیو ساخته نمی‌شود.');
    }

    /**
     * ویرایش کامل تنظیمات یک سفارش؛ ذخیرهٔ ساده خروجی قبلی را دست‌نخورده نگه می‌دارد
     * و «ذخیره و ساخت مجدد» همان سفارش را با تنظیمات تازه به ورکفلو می‌فرستد.
     */
    public function updateJobSettings(Request $request, VideoStudioJob $job)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'source_mode' => ['required', Rule::in(['auto', 'upload', 'music', 'video'])],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'source_file' => ['nullable', 'file', 'mimes:mp3,wav,m4a,ogg,mp4,mov,webm', 'max:102400'],
            'source_library_id' => ['nullable', 'integer', 'exists:video_studio_sources,id'],
            'selected_images_text' => ['nullable', 'string', 'max:20000'],
            'aspect_ratio' => ['required', Rule::in(['9:16', '1:1', '4:5', '16:9'])],
            'instagram_enabled' => ['nullable', 'boolean'],
            'telegram_enabled' => ['nullable', 'boolean'],
            'youtube_enabled' => ['nullable', 'boolean'],
            'aparat_enabled' => ['nullable', 'boolean'],
            'linkedin_enabled' => ['nullable', 'boolean'],
            'telegram_send_video' => ['nullable', 'boolean'],
            'telegram_send_images' => ['nullable', 'boolean'],
            'instagram_send_video' => ['nullable', 'boolean'],
            'instagram_send_images' => ['nullable', 'boolean'],
            'youtube_send_video' => ['nullable', 'boolean'],
            'youtube_send_images' => ['nullable', 'boolean'],
            'aparat_send_video' => ['nullable', 'boolean'],
            'aparat_send_images' => ['nullable', 'boolean'],
            'linkedin_send_video' => ['nullable', 'boolean'],
            'linkedin_send_images' => ['nullable', 'boolean'],
            'cta_enabled' => ['nullable', 'boolean'],
            'cta_text' => ['nullable', 'string', 'max:1000'],
            'cta_background' => ['nullable', 'string', 'max:60', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! $this->isKnownHookColor((string) $value, 'background')) {
                    $fail('رنگ پس‌زمینه CTA معتبر نیست.');
                }
            }],
            'cta_text_color' => ['nullable', 'string', 'max:60', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! $this->isKnownHookColor((string) $value, 'text')) {
                    $fail('رنگ متن CTA معتبر نیست.');
                }
            }],
            'cta_font_size' => ['nullable', 'numeric', 'between:20,72'],
            'cta_font_weight' => ['nullable', 'integer', 'between:1,5'],
            'cta_scale' => ['nullable', 'numeric', 'between:0.7,1.5'],
            'cta_vertical_offset' => ['nullable', 'numeric', 'between:-45,45'],
            'cta_guidelines' => ['nullable', 'string', 'max:5000'],
            'cta_duration' => ['nullable', 'numeric', 'between:0.1,5'],
            'cta_duration_mode' => ['nullable', Rule::in(['manual', 'auto'])],
            'transition' => ['nullable', Rule::in(['cut', 'fade', 'blur', 'slide'])],
            'transition_duration' => ['nullable', 'numeric', 'between:0.2,1.5'],
            'text_command' => ['nullable', 'string', 'max:5000'],
            'font_family' => ['required', Schema::hasTable('video_studio_fonts')
                ? Rule::exists('video_studio_fonts', 'slug')->where('is_active', true)
                : Rule::in(['B_Yekan'])],
            'hook_text' => ['nullable', 'string', 'max:1000'],
            'caption_text' => ['nullable', 'string', 'max:5000'],
            'keyword' => ['nullable', 'string', 'max:80'],
            'dm_template' => ['nullable', 'string', 'max:5000'],
            'telegram_caption_text' => ['nullable', 'string', 'max:5000'],
            'youtube_caption_text' => ['nullable', 'string', 'max:5000'],
            'aparat_caption_text' => ['nullable', 'string', 'max:5000'],
            'linkedin_caption_text' => ['nullable', 'string', 'max:5000'],
            'instagram_prompt' => ['nullable', 'string', 'max:30000'],
            'telegram_prompt' => ['nullable', 'string', 'max:30000'],
            'youtube_prompt' => ['nullable', 'string', 'max:30000'],
            'aparat_prompt' => ['nullable', 'string', 'max:30000'],
            'linkedin_prompt' => ['nullable', 'string', 'max:30000'],
            'telegram_buttons_enabled' => ['nullable', 'boolean'],
            'telegram_button_label' => ['nullable', 'array', 'max:8'],
            'telegram_button_label.*' => ['nullable', 'string', 'max:80'],
            'telegram_button_url' => ['nullable', 'array', 'max:8'],
            'telegram_button_url.*' => ['nullable', 'url', 'max:2048'],
            'telegram_button_style' => ['nullable', 'array', 'max:8'],
            'telegram_button_style.*' => ['nullable', Rule::in(['primary', 'success', 'danger'])],
            'telegram_button_width' => ['nullable', 'array', 'max:8'],
            'telegram_button_width.*' => ['nullable', Rule::in(['full', 'half'])],
            'build_now' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('source_file')) {
            $data['source_url'] = asset('storage/' . ltrim($request->file('source_file')->store('video-studio/sources', 'public'), '/'));
        }
        if (!empty($data['source_library_id'])) {
            $librarySource = VideoStudioSource::query()->where('is_active', true)->findOrFail((int) $data['source_library_id']);
            $data['source_mode'] = $librarySource->type;
            $data['source_url'] = $librarySource->source_url;
        }
        if (($data['source_mode'] ?? null) === 'auto' && empty($data['source_library_id'])) {
            $data['source_url'] = null;
        }
        if (($data['source_mode'] ?? null) === 'auto' && blank($data['source_url'])) {
            $librarySource = VideoStudioSource::query()
                ->where('is_active', true)
                ->whereNotNull('source_url')
                ->where('source_url', '<>', '')
                ->orderBy('used_count')
                ->orderBy('id')
                ->first();
            if ($librarySource) {
                $data['source_library_id'] = $librarySource->id;
                $data['source_mode'] = $librarySource->type;
                $data['source_url'] = $librarySource->source_url;
            }
        }
        $sourceError = blank($data['source_url'])
            ? 'منبع صوت یا ویدیو معتبر انتخاب نشده است.'
            : null;
        $selectedImages = collect(preg_split('/\R/u', (string) ($data['selected_images_text'] ?? '')))
            ->map(fn ($url): string => trim((string) $url))
            ->filter()
            ->unique()
            ->take(10)
            ->values()
            ->all();
        $buttons = $this->normalizeTelegramButtons($request);
        $ctaBackground = $this->resolveHookColor((string) ($data['cta_background'] ?? data_get($job->payload, 'cta_background', 'primary')), 'background');
        $ctaTextColor = $this->resolveHookColor((string) ($data['cta_text_color'] ?? data_get($job->payload, 'cta_text_color', 'light')), 'text');
        $data['cta_background'] = $ctaBackground['key'];
        $data['cta_background_color'] = $ctaBackground['render_value'];
        $data['cta_text_color'] = $ctaTextColor['key'];
        $data['cta_text_color_value'] = $ctaTextColor['render_value'];
        $payload = is_array($job->payload) ? $job->payload : [];
        if (Schema::hasTable('video_studio_social_prompts')) {
            $savedPrompts = VideoStudioSocialPrompt::query()
                ->where('admin_id', auth('admin')->id())
                ->pluck('prompt', 'platform');
            foreach (['instagram', 'telegram', 'youtube', 'aparat', 'linkedin'] as $platform) {
                $key = $platform . '_prompt';
                if (blank($data[$key] ?? null) && filled($savedPrompts[$platform] ?? null)) {
                    $data[$key] = (string) $savedPrompts[$platform];
                }
            }
        }
        $buildNow = $request->boolean('build_now');
        $instagramEnabled = $request->has('instagram_enabled') ? $request->boolean('instagram_enabled') : (bool) data_get($payload, 'instagram_enabled', true);
        $telegramEnabled = $request->has('telegram_enabled') ? $request->boolean('telegram_enabled') : (bool) data_get($payload, 'telegram_enabled', true);
        $youtubeEnabled = $request->has('youtube_enabled') ? $request->boolean('youtube_enabled') : (bool) data_get($payload, 'youtube_enabled', false);
        $aparatEnabled = $request->has('aparat_enabled') ? $request->boolean('aparat_enabled') : (bool) data_get($payload, 'aparat_enabled', false);
        $linkedinEnabled = $request->has('linkedin_enabled') ? $request->boolean('linkedin_enabled') : (bool) data_get($payload, 'linkedin_enabled', false);
        $platformError = (!$instagramEnabled && !$telegramEnabled && !$youtubeEnabled && !$aparatEnabled && !$linkedinEnabled)
            ? 'حداقل یکی از خروجی‌های شبکه‌های اجتماعی باید روشن باشد.'
            : null;
        $payload = array_merge($payload, [
            'font_family' => (string) $data['font_family'],
            'instagram_prompt' => (string) ($data['instagram_prompt'] ?? ''),
            'telegram_prompt' => (string) ($data['telegram_prompt'] ?? ''),
            'youtube_prompt' => (string) ($data['youtube_prompt'] ?? ''),
            'aparat_prompt' => (string) ($data['aparat_prompt'] ?? ''),
            'linkedin_prompt' => (string) ($data['linkedin_prompt'] ?? ''),
            'telegram_caption_text' => (string) ($data['telegram_caption_text'] ?? ''),
            'youtube_caption_text' => (string) ($data['youtube_caption_text'] ?? ''),
            'aparat_caption_text' => (string) ($data['aparat_caption_text'] ?? ''),
            'linkedin_caption_text' => (string) ($data['linkedin_caption_text'] ?? ''),
            'telegram_buttons' => $buttons,
            'instagram_enabled' => $instagramEnabled,
            'telegram_enabled' => $telegramEnabled,
            'youtube_enabled' => $youtubeEnabled,
            'aparat_enabled' => $aparatEnabled,
            'linkedin_enabled' => $linkedinEnabled,
            'telegram_send_video' => $request->has('telegram_send_video') ? $request->boolean('telegram_send_video') : (bool) data_get($payload, 'telegram_send_video', true),
            'telegram_send_images' => $request->has('telegram_send_images') ? $request->boolean('telegram_send_images') : (bool) data_get($payload, 'telegram_send_images', false),
            'instagram_send_video' => $request->has('instagram_send_video') ? $request->boolean('instagram_send_video') : (bool) data_get($payload, 'instagram_send_video', true),
            'instagram_send_images' => $request->has('instagram_send_images') ? $request->boolean('instagram_send_images') : (bool) data_get($payload, 'instagram_send_images', false),
            'youtube_send_video' => $request->has('youtube_send_video') ? $request->boolean('youtube_send_video') : (bool) data_get($payload, 'youtube_send_video', false),
            'youtube_send_images' => $request->has('youtube_send_images') ? $request->boolean('youtube_send_images') : (bool) data_get($payload, 'youtube_send_images', false),
            'aparat_send_video' => $request->has('aparat_send_video') ? $request->boolean('aparat_send_video') : (bool) data_get($payload, 'aparat_send_video', false),
            'aparat_send_images' => $request->has('aparat_send_images') ? $request->boolean('aparat_send_images') : (bool) data_get($payload, 'aparat_send_images', false),
            'linkedin_send_video' => $request->has('linkedin_send_video') ? $request->boolean('linkedin_send_video') : (bool) data_get($payload, 'linkedin_send_video', false),
            'linkedin_send_images' => $request->has('linkedin_send_images') ? $request->boolean('linkedin_send_images') : (bool) data_get($payload, 'linkedin_send_images', false),
            'cta_enabled' => $request->has('cta_enabled') ? $request->boolean('cta_enabled') : (bool) data_get($payload, 'cta_enabled', true),
            'cta_text' => (string) ($data['cta_text'] ?? data_get($payload, 'cta_text', '')),
            'cta_background' => (string) ($data['cta_background'] ?? data_get($payload, 'cta_background', 'primary')),
            'cta_background_color' => (string) ($data['cta_background_color'] ?? data_get($payload, 'cta_background_color', '#16594F')),
            'cta_text_color' => (string) ($data['cta_text_color'] ?? data_get($payload, 'cta_text_color', 'light')),
            'cta_text_color_value' => (string) ($data['cta_text_color_value'] ?? data_get($payload, 'cta_text_color_value', '#FFFFFF')),
            'cta_font_size' => (float) ($data['cta_font_size'] ?? data_get($payload, 'cta_font_size', 36)),
            'cta_font_weight' => (int) ($data['cta_font_weight'] ?? data_get($payload, 'cta_font_weight', 3)),
            'cta_scale' => (float) ($data['cta_scale'] ?? data_get($payload, 'cta_scale', 1)),
            'cta_vertical_offset' => (float) ($data['cta_vertical_offset'] ?? data_get($payload, 'cta_vertical_offset', 0)),
            'cta_guidelines' => (string) ($data['cta_guidelines'] ?? data_get($payload, 'cta_guidelines', '')),
            'cta_duration' => (float) ($data['cta_duration'] ?? data_get($payload, 'cta_duration', 2)),
            'cta_duration_mode' => (string) ($data['cta_duration_mode'] ?? data_get($payload, 'cta_duration_mode', 'manual')),
            'transition' => (string) ($data['transition'] ?? data_get($payload, 'transition', 'cut')),
            'transition_duration' => (float) ($data['transition_duration'] ?? data_get($payload, 'transition_duration', 0.5)),
            'text_command' => trim((string) ($data['text_command'] ?? data_get($payload, 'text_command', ''))),
            'render_config' => [
                'font_family' => (string) $data['font_family'],
                'hook_background' => (string) data_get($payload, 'hook_background', 'primary'),
                'hook_position' => (string) data_get($payload, 'hook_position', 'center'),
                'cta_position' => (string) data_get($payload, 'cta_position', 'bottom'),
                'cta_background' => (string) ($data['cta_background'] ?? data_get($payload, 'cta_background', 'primary')),
                'cta_background_color' => (string) ($data['cta_background_color'] ?? data_get($payload, 'cta_background_color', '#16594F')),
                'cta_text_color' => (string) ($data['cta_text_color'] ?? data_get($payload, 'cta_text_color', 'light')),
                'cta_text_color_value' => (string) ($data['cta_text_color_value'] ?? data_get($payload, 'cta_text_color_value', '#FFFFFF')),
                'cta_font_size' => (float) ($data['cta_font_size'] ?? data_get($payload, 'cta_font_size', 36)),
                'cta_font_weight' => (int) ($data['cta_font_weight'] ?? data_get($payload, 'cta_font_weight', 3)),
                'cta_scale' => (float) ($data['cta_scale'] ?? data_get($payload, 'cta_scale', 1)),
                'cta_vertical_offset' => (float) ($data['cta_vertical_offset'] ?? data_get($payload, 'cta_vertical_offset', 0)),
                'cta_guidelines' => (string) ($data['cta_guidelines'] ?? data_get($payload, 'cta_guidelines', '')),
                'cta_duration' => (float) ($data['cta_duration'] ?? data_get($payload, 'cta_duration', 2)),
                'cta_duration_mode' => (string) ($data['cta_duration_mode'] ?? data_get($payload, 'cta_duration_mode', 'manual')),
                'cta_enabled' => $request->has('cta_enabled') ? $request->boolean('cta_enabled') : (bool) data_get($payload, 'cta_enabled', true),
                'cta_text' => (string) ($data['cta_text'] ?? data_get($payload, 'cta_text', '')),
                'transition' => (string) ($data['transition'] ?? data_get($payload, 'transition', 'cut')),
                'transition_duration' => (float) ($data['transition_duration'] ?? data_get($payload, 'transition_duration', 0.5)),
            ],
            'estimated_cost' => $this->estimateVideoCost((int) $data['product_id'], (string) $data['aspect_ratio']),
            'source_library_id' => (int) ($data['source_library_id'] ?? 0) ?: null,
            'source_fingerprint' => hash('sha256', json_encode([
                'product_id' => (int) $data['product_id'],
                'source_mode' => (string) $data['source_mode'],
                'source_url' => (string) ($data['source_url'] ?? ''),
                'selected_images' => $selectedImages,
                'aspect_ratio' => (string) $data['aspect_ratio'],
                'cta_duration' => (float) ($data['cta_duration'] ?? data_get($payload, 'cta_duration', 2)),
                'cta_duration_mode' => (string) ($data['cta_duration_mode'] ?? data_get($payload, 'cta_duration_mode', 'manual')),
                'cta_enabled' => $request->has('cta_enabled') ? $request->boolean('cta_enabled') : (bool) data_get($payload, 'cta_enabled', true),
                'cta_text' => (string) ($data['cta_text'] ?? data_get($payload, 'cta_text', '')),
                'cta_background' => (string) ($data['cta_background'] ?? data_get($payload, 'cta_background', 'primary')),
                'cta_text_color' => (string) ($data['cta_text_color'] ?? data_get($payload, 'cta_text_color', 'light')),
                'cta_font_size' => (float) ($data['cta_font_size'] ?? data_get($payload, 'cta_font_size', 36)),
                'cta_font_weight' => (int) ($data['cta_font_weight'] ?? data_get($payload, 'cta_font_weight', 3)),
                'cta_scale' => (float) ($data['cta_scale'] ?? data_get($payload, 'cta_scale', 1)),
                'cta_vertical_offset' => (float) ($data['cta_vertical_offset'] ?? data_get($payload, 'cta_vertical_offset', 0)),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'build_now' => $buildNow,
        ]);
        unset($data['source_file'], $data['source_library_id'], $data['selected_images_text'], $data['telegram_buttons_enabled'], $data['telegram_button_label'], $data['telegram_button_url'], $data['telegram_button_style'], $data['telegram_button_width'], $data['build_now'], $data['instagram_enabled'], $data['telegram_enabled'], $data['youtube_enabled'], $data['aparat_enabled'], $data['linkedin_enabled'], $data['telegram_send_video'], $data['telegram_send_images'], $data['instagram_send_video'], $data['instagram_send_images'], $data['youtube_send_video'], $data['youtube_send_images'], $data['aparat_send_video'], $data['aparat_send_images'], $data['linkedin_send_video'], $data['linkedin_send_images'], $data['cta_enabled'], $data['cta_text'], $data['cta_background'], $data['cta_background_color'], $data['cta_text_color'], $data['cta_text_color_value'], $data['cta_font_size'], $data['cta_font_weight'], $data['cta_scale'], $data['cta_vertical_offset'], $data['cta_guidelines'], $data['cta_duration'], $data['cta_duration_mode'], $data['transition'], $data['transition_duration'], $data['text_command']);
        $data['selected_images'] = $selectedImages ?: (array) $job->selected_images;
        $data['payload'] = $payload;
        if ($buildNow) {
            $data['status'] = ($sourceError || $platformError) ? 'failed' : 'queued';
            $data['error_message'] = $sourceError ?: $platformError;
            $data['started_at'] = null;
            $data['completed_at'] = null;
            $data['video_url'] = null;
        }
        $job->fill($data)->save();
        if ($buildNow && !$sourceError && !$platformError) {
            $this->dispatchJobToWorkflow($job->fresh());
        }

        return back()->with(($sourceError || $platformError) && $buildNow ? 'warning' : 'success', ($sourceError || $platformError) && $buildNow
            ? 'تنظیمات ذخیره شد اما پیش از ساخت، اعتبارسنجی ناموفق بود.'
            : ($buildNow ? 'تنظیمات ذخیره و ساخت مجدد آغاز شد.' : 'تنظیمات سفارش ذخیره شد.'));
    }

    public function retryJob(VideoStudioJob $job)
    {
        if (!$this->ensureJobSource($job)) {
            $job->update(['status' => 'failed', 'error_message' => 'منبع صوت یا ویدیو برای این سفارش موجود نیست؛ ابتدا یک منبع معتبر انتخاب کنید.']);
            return back()->withErrors(['source_file' => 'منبع این سفارش موجود نیست؛ از ویرایش کامل، فایل یا منبع کتابخانه را انتخاب کنید.']);
        }
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
            if (!$this->ensureJobSource($job)) {
                $job->update(['status' => 'failed', 'error_message' => 'منبع صوت یا ویدیو برای این سفارش موجود نیست؛ ابتدا یک منبع معتبر انتخاب کنید.']);
                return;
            }
            $job->update(['status' => 'queued', 'error_message' => null, 'started_at' => null, 'completed_at' => null]);
            $this->dispatchJobToWorkflow($job->fresh());
        });

        return back()->with('success', 'سفارش‌های انتخاب‌شده برای ساخت مجدد ارسال شدند.');
    }

    /**
     * فایل هر وزن واقعی فونت‌های منتخب ویدیو. برای فونت‌های افزوده‌شده توسط مدیر، همان فایل
     * ثبت‌شده به‌عنوان وزن پایه استفاده می‌شود تا انتخاب فونت هرگز از کار نیفتد.
     */
    private function videoStudioFontWeightAssets(iterable $fonts): array
    {
        $assets = $this->videoStudioFontWeightAssetCatalog();

        foreach ($fonts as $font) {
            $slug = (string) ($font->slug ?? '');
            if ($slug !== '' && !isset($assets[$slug]) && filled($font->file_path ?? null)) {
                $assets[$slug] = [400 => (string) $font->file_path];
            }
        }

        return $assets;
    }

    private function videoStudioFontWeightAssetCatalog(): array
    {
        return [
            'B_Yekan' => [400 => 'fonts/B_Yekan.ttf'],
            'Abar' => [300 => 'fonts/video/AbarMid-Regular.ttf', 400 => 'fonts/video/AbarMid-Regular.ttf', 500 => 'fonts/video/AbarMid-SemiBold.ttf', 700 => 'fonts/video/AbarMid-Bold.ttf', 900 => 'fonts/video/AbarMid-Black.ttf'],
            'IRANSansX' => [300 => 'fonts/IRANSansXFaNum-LightD4.ttf', 400 => 'fonts/IRANSansXFaNum-RegularD4.ttf', 500 => 'fonts/IRANSansXFaNum-MediumD4.ttf', 700 => 'fonts/IRANSansXFaNum-BoldD4.ttf', 900 => 'fonts/IRANSansXFaNum-BlackD4.ttf'],
            'Peyda' => [300 => 'fonts/video/peyda-light.ttf', 400 => 'fonts/video/Peyda-Medium.ttf', 500 => 'fonts/video/Peyda-Medium.ttf', 700 => 'fonts/video/Peyda-SemiBold.ttf', 900 => 'fonts/video/Peyda-Black.ttf'],
            'Doran' => [300 => 'fonts/video/Doran-Light.ttf', 400 => 'fonts/video/Doran-Regular.ttf', 500 => 'fonts/video/Doran-Medium.ttf', 700 => 'fonts/video/Doran-Bold.ttf', 900 => 'fonts/video/Doran-ExtraBold.ttf'],
            'Modam' => [300 => 'fonts/video/Modam-ExtraLight.ttf', 400 => 'fonts/video/Modam-Medium.ttf', 500 => 'fonts/video/Modam-Medium.ttf', 700 => 'fonts/video/Modam-SemiBold.ttf', 900 => 'fonts/video/Modam-Black.ttf'],
            'YekanBakh' => [300 => 'fonts/video/YekanBakh-Light.ttf', 400 => 'fonts/YekanBakh-Regular.ttf', 500 => 'fonts/video/YekanBakh-Medium.ttf', 700 => 'fonts/video/YekanBakh-Bold.ttf', 900 => 'fonts/video/YekanBakh-Heavy.ttf'],
        ];
    }

    private function hookFontWeightValue(int $weight): int
    {
        return [1 => 300, 2 => 400, 3 => 500, 4 => 700, 5 => 900][$weight] ?? 500;
    }

    private function videoStudioFontPathForWeight(string $family, int $weight, ?string $fallbackPath = null): ?string
    {
        $assets = $this->videoStudioFontWeightAssetCatalog()[$family] ?? [];
        if ($assets === []) {
            return $fallbackPath;
        }

        $availableWeights = array_keys($assets);
        usort($availableWeights, static fn (int $left, int $right): int => abs($left - $weight) <=> abs($right - $weight));

        return $assets[$availableWeights[0]] ?? $fallbackPath;
    }

    /**
     * رنگ‌های پایه از توکن‌های موجود پنل می‌آیند؛ رنگ سفارشی فقط برای همان مدیر نگه‌داری می‌شود.
     * مقدار render_value به ورکفلو داده می‌شود تا خروجی با پیش‌نمایش هم‌خوان بماند.
     */
    private function hookColorOptions(string $target): array
    {
        $defaults = $this->defaultHookColors($target);
        if (! Schema::hasTable('video_studio_hook_colors') || ! auth('admin')->check()) {
            return $defaults;
        }

        if (Schema::hasTable('video_studio_hook_color_preferences')) {
            $hiddenKeys = VideoStudioHookColorPreference::query()
                ->where('admin_id', auth('admin')->id())
                ->where('target', $target)
                ->where('is_hidden', true)
                ->pluck('color_key')
                ->all();
            $defaults = array_values(array_filter($defaults, static fn (array $color): bool => !in_array($color['key'], $hiddenKeys, true)));
        }

        $custom = VideoStudioHookColor::query()
            ->where('admin_id', auth('admin')->id())
            ->where('target', $target)
            ->latest('id')
            ->get()
            ->map(fn (VideoStudioHookColor $color): array => $this->hookColorPayload($color))
            ->all();

        return array_merge($defaults, $custom);
    }

    private function defaultHookColors(string $target): array
    {
        $colors = [
            'background' => [
                ['key' => 'primary', 'name' => 'سبز وطن', 'css_value' => 'var(--primary)', 'render_value' => '#16594F', 'is_custom' => false],
                ['key' => 'dark', 'name' => 'مشکی', 'css_value' => 'var(--text-h)', 'render_value' => '#000000', 'is_custom' => false],
                ['key' => 'light', 'name' => 'سفید', 'css_value' => 'var(--card-bg)', 'render_value' => '#FFFFFF', 'is_custom' => false],
                ['key' => 'neutral', 'name' => 'خاکستری', 'css_value' => 'var(--text-soft)', 'render_value' => '#686E6B', 'is_custom' => false],
            ],
            'text' => [
                ['key' => 'dark', 'name' => 'مشکی', 'css_value' => 'var(--text-h)', 'render_value' => '#000000', 'is_custom' => false],
                ['key' => 'light', 'name' => 'سفید', 'css_value' => 'var(--card-bg)', 'render_value' => '#FFFFFF', 'is_custom' => false],
                ['key' => 'primary', 'name' => 'سبز وطن', 'css_value' => 'var(--primary)', 'render_value' => '#16594F', 'is_custom' => false],
                ['key' => 'neutral', 'name' => 'خاکستری', 'css_value' => 'var(--text-soft)', 'render_value' => '#686E6B', 'is_custom' => false],
            ],
        ];

        return $colors[$target] ?? [];
    }

    private function hookColorPayload(VideoStudioHookColor $color): array
    {
        return [
            'key' => 'custom-' . $color->id,
            'name' => $color->name,
            'css_value' => $color->color_value,
            'render_value' => $color->color_value,
            'is_custom' => true,
            'id' => $color->id,
        ];
    }

    private function resolveHookColor(string $key, string $target): array
    {
        foreach ($this->defaultHookColors($target) as $color) {
            if ($color['key'] === $key) {
                return $color;
            }
        }
        if (preg_match('/^custom-(\d+)$/', $key, $matches) && Schema::hasTable('video_studio_hook_colors')) {
            $color = VideoStudioHookColor::query()
                ->whereKey((int) $matches[1])
                ->where('admin_id', auth('admin')->id())
                ->where('target', $target)
                ->first();
            if ($color) {
                return $this->hookColorPayload($color);
            }
        }

        return $this->defaultHookColors($target)[0];
    }

    private function isKnownHookColor(string $key, string $target): bool
    {
        if ($key === '') {
            return true;
        }

        return $this->resolveHookColor($key, $target)['key'] === $key;
    }

    private function normalizeTelegramButtons(Request $request): array
    {
        if (!$request->boolean('telegram_buttons_enabled')) {
            return [];
        }

        $labels = $request->input('telegram_button_label', []);
        $urls = $request->input('telegram_button_url', []);
        $styles = $request->input('telegram_button_style', []);
        $widths = $request->input('telegram_button_width', []);
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
                'width' => in_array(($widths[$index] ?? 'full'), ['full', 'half'], true)
                    ? (string) $widths[$index]
                    : 'full',
            ];
        }

        return array_slice($buttons, 0, 8);
    }

    /**
     * برآورد سبک و بدون فراخوانی زندهٔ سرویس‌دهنده؛ نرخ از جدول نرخ ارز موجود خوانده می‌شود.
     */
    private function estimateVideoCost(int $productId, string $aspectRatio): array
    {
        if (! Schema::hasTable('finance_exchange_rates') || ! Schema::hasColumn('finance_exchange_rates', 'rate_to_toman')) {
            return [];
        }

        $product = Product::query()->find($productId);
        $model = $product && filled($product->primary_model)
            ? AiModel::query()->where('openrouter_model_id', $product->primary_model)->first()
            : null;
        if (! $model) {
            return [];
        }

        $unitUsd = app(StudioCostService::class)->modelUnitPrice($model, 'video', '', null, $aspectRatio);
        $rateToman = (float) FinanceExchangeRate::query()
            ->where('currency', 'USD')
            ->where('rate_to_toman', '>', 0)
            ->latest('rate_date')
            ->value('rate_to_toman');
        if (! is_numeric($unitUsd) || (float) $unitUsd <= 0 || $rateToman <= 0) {
            return [];
        }

        return [
            'usd' => round((float) $unitUsd, 6),
            'toman' => round((float) $unitUsd * $rateToman, 2),
            'rate_toman' => round($rateToman, 2),
            'source' => 'قیمت مدل و نرخ ارز ثبت‌شده',
        ];
    }

    private function dispatchJobToWorkflow(VideoStudioJob $job): void
    {
        if (!$this->ensureJobSource($job)) {
            $job->update(['status' => 'failed', 'error_message' => 'منبع صوت یا ویدیو برای این سفارش موجود نیست؛ ابتدا یک منبع معتبر انتخاب کنید.']);
            return;
        }
        $job = $job->fresh();
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
        $fontFamily = (string) ($payload['font_family'] ?? 'B_Yekan');
        $font = Schema::hasTable('video_studio_fonts')
            ? VideoStudioFont::query()->where('is_active', true)->where('slug', $fontFamily)->first()
            : null;
        $hookFontWeight = max(1, min(5, (int) ($payload['hook_font_weight'] ?? 3)));
        $hookFontWeightValue = $this->hookFontWeightValue($hookFontWeight);
        $fontFilePath = $this->videoStudioFontPathForWeight($fontFamily, $hookFontWeightValue, $font?->file_path);
        $fontFileUrl = filled($fontFilePath)
            ? (Str::startsWith($fontFilePath, ['http://', 'https://']) ? $fontFilePath : asset(ltrim($fontFilePath, '/')))
            : '';
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
                'font_family' => $fontFamily,
                'font_file_url' => $fontFileUrl,
                'hook_font_file_url' => $fontFileUrl,
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
                'instagram_prompt' => (string) ($payload['instagram_prompt'] ?? ''),
                'telegram_prompt' => (string) ($payload['telegram_prompt'] ?? ''),
                'youtube_prompt' => (string) ($payload['youtube_prompt'] ?? ''),
                'aparat_prompt' => (string) ($payload['aparat_prompt'] ?? ''),
                'linkedin_prompt' => (string) ($payload['linkedin_prompt'] ?? ''),
                'telegram_caption_text' => (string) ($payload['telegram_caption_text'] ?? ''),
                'youtube_caption_text' => (string) ($payload['youtube_caption_text'] ?? ''),
                'aparat_caption_text' => (string) ($payload['aparat_caption_text'] ?? ''),
                'linkedin_caption_text' => (string) ($payload['linkedin_caption_text'] ?? ''),
                'telegram_buttons' => is_array($payload['telegram_buttons'] ?? null) ? $payload['telegram_buttons'] : [],
                'instagram_enabled' => (bool) ($payload['instagram_enabled'] ?? true),
                'telegram_enabled' => (bool) ($payload['telegram_enabled'] ?? true),
                'youtube_enabled' => (bool) ($payload['youtube_enabled'] ?? false),
                'aparat_enabled' => (bool) ($payload['aparat_enabled'] ?? false),
                'linkedin_enabled' => (bool) ($payload['linkedin_enabled'] ?? false),
                'telegram_send_video' => (bool) ($payload['telegram_send_video'] ?? true),
                'telegram_send_images' => (bool) ($payload['telegram_send_images'] ?? false),
                'instagram_send_video' => (bool) ($payload['instagram_send_video'] ?? true),
                'instagram_send_images' => (bool) ($payload['instagram_send_images'] ?? false),
                'youtube_send_video' => (bool) ($payload['youtube_send_video'] ?? false),
                'youtube_send_images' => (bool) ($payload['youtube_send_images'] ?? false),
                'aparat_send_video' => (bool) ($payload['aparat_send_video'] ?? false),
                'aparat_send_images' => (bool) ($payload['aparat_send_images'] ?? false),
                'linkedin_send_video' => (bool) ($payload['linkedin_send_video'] ?? false),
                'linkedin_send_images' => (bool) ($payload['linkedin_send_images'] ?? false),
                'cta_enabled' => (bool) ($payload['cta_enabled'] ?? true),
                'cta_text' => (string) ($payload['cta_text'] ?? ''),
                'cta_background' => (string) ($payload['cta_background'] ?? 'primary'),
                'cta_background_color' => (string) ($payload['cta_background_color'] ?? '#16594F'),
                'cta_text_color' => (string) ($payload['cta_text_color'] ?? 'light'),
                'cta_text_color_value' => (string) ($payload['cta_text_color_value'] ?? '#FFFFFF'),
                'cta_font_size' => (float) ($payload['cta_font_size'] ?? 36),
                'cta_font_weight' => (int) ($payload['cta_font_weight'] ?? 3),
                'cta_scale' => (float) ($payload['cta_scale'] ?? 1),
                'cta_vertical_offset' => (float) ($payload['cta_vertical_offset'] ?? 0),
                'cta_guidelines' => (string) ($payload['cta_guidelines'] ?? ''),
                'cta_duration' => (float) ($payload['cta_duration'] ?? 2),
                'cta_duration_mode' => (string) ($payload['cta_duration_mode'] ?? 'manual'),
                'hook_background' => (string) ($payload['hook_background'] ?? 'primary'),
                'hook_background_color' => (string) ($payload['hook_background_color'] ?? '#16594F'),
                'hook_text_color' => (string) ($payload['hook_text_color'] ?? 'light'),
                'hook_text_color_value' => (string) ($payload['hook_text_color_value'] ?? '#FFFFFF'),
                'hook_font_size' => (float) ($payload['hook_font_size'] ?? 36),
                'hook_font_weight' => $hookFontWeight,
                'hook_font_weight_value' => $hookFontWeightValue,
                'hook_scale' => (float) ($payload['hook_scale'] ?? 1),
                'hook_vertical_offset' => (float) ($payload['hook_vertical_offset'] ?? 0),
                'hook_duration' => (float) ($payload['hook_duration'] ?? 2),
                'hook_duration_mode' => (string) ($payload['hook_duration_mode'] ?? 'manual'),
                'image_sequence' => is_array($payload['image_sequence'] ?? null) ? $payload['image_sequence'] : [],
                'hook_position' => (string) ($payload['hook_position'] ?? 'center'),
                'cta_position' => (string) ($payload['cta_position'] ?? 'bottom'),
                'transition' => (string) ($payload['transition'] ?? 'cut'),
                'transition_duration' => (float) ($payload['transition_duration'] ?? 0.5),
                'text_command' => (string) ($payload['text_command'] ?? ''),
                'render_config' => is_array($payload['render_config'] ?? null) ? $payload['render_config'] : [
                    'font_family' => $fontFamily,
                    'hook_background' => (string) ($payload['hook_background'] ?? 'primary'),
                    'hook_background_color' => (string) ($payload['hook_background_color'] ?? '#16594F'),
                    'hook_text_color' => (string) ($payload['hook_text_color'] ?? 'light'),
                    'hook_text_color_value' => (string) ($payload['hook_text_color_value'] ?? '#FFFFFF'),
                    'hook_font_size' => (float) ($payload['hook_font_size'] ?? 36),
                    'hook_font_weight' => $hookFontWeight,
                    'hook_font_weight_value' => $hookFontWeightValue,
                    'hook_font_file_url' => $fontFileUrl,
                    'hook_scale' => (float) ($payload['hook_scale'] ?? 1),
                    'hook_vertical_offset' => (float) ($payload['hook_vertical_offset'] ?? 0),
                    'hook_duration' => (float) ($payload['hook_duration'] ?? 2),
                    'hook_duration_mode' => (string) ($payload['hook_duration_mode'] ?? 'manual'),
                    'image_sequence' => is_array($payload['image_sequence'] ?? null) ? $payload['image_sequence'] : [],
                    'hook_position' => (string) ($payload['hook_position'] ?? 'center'),
                    'cta_position' => (string) ($payload['cta_position'] ?? 'bottom'),
                    'cta_background' => (string) ($payload['cta_background'] ?? 'primary'),
                    'cta_background_color' => (string) ($payload['cta_background_color'] ?? '#16594F'),
                    'cta_text_color' => (string) ($payload['cta_text_color'] ?? 'light'),
                    'cta_text_color_value' => (string) ($payload['cta_text_color_value'] ?? '#FFFFFF'),
                    'cta_font_size' => (float) ($payload['cta_font_size'] ?? 36),
                    'cta_font_weight' => (int) ($payload['cta_font_weight'] ?? 3),
                    'cta_scale' => (float) ($payload['cta_scale'] ?? 1),
                    'cta_vertical_offset' => (float) ($payload['cta_vertical_offset'] ?? 0),
                    'cta_guidelines' => (string) ($payload['cta_guidelines'] ?? ''),
                    'cta_duration' => (float) ($payload['cta_duration'] ?? 2),
                    'cta_duration_mode' => (string) ($payload['cta_duration_mode'] ?? 'manual'),
                    'transition' => (string) ($payload['transition'] ?? 'cut'),
                    'transition_duration' => (float) ($payload['transition_duration'] ?? 0.5),
                ],
                'video_code' => method_exists($job, 'shortCode') ? $job->shortCode() : ('P' . strtoupper(base_convert((string) $job->id, 10, 36))),
                'telegram_topics' => [
                    'chat_id' => (string) config('services.n8n.video_studio_telegram_chat_id', ''),
                    'instagram_thread_id' => (string) config('services.n8n.video_studio_telegram_instagram_thread_id', ''),
                    'telegram_thread_id' => (string) config('services.n8n.video_studio_telegram_channel_thread_id', ''),
                    'music_thread_id' => (string) config('services.n8n.video_studio_telegram_music_thread_id', ''),
                    'linkedin_thread_id' => (string) config('services.n8n.video_studio_telegram_linkedin_thread_id', '29'),
                    'aparat_thread_id' => (string) config('services.n8n.video_studio_telegram_aparat_thread_id', '31'),
                    'youtube_thread_id' => (string) config('services.n8n.video_studio_telegram_youtube_thread_id', '33'),
                    'platform_topics' => [
                        'instagram' => (string) config('services.n8n.video_studio_telegram_instagram_thread_id', '4'),
                        'telegram' => (string) config('services.n8n.video_studio_telegram_channel_thread_id', '2'),
                        'linkedin' => (string) config('services.n8n.video_studio_telegram_linkedin_thread_id', '29'),
                        'aparat' => (string) config('services.n8n.video_studio_telegram_aparat_thread_id', '31'),
                        'youtube' => (string) config('services.n8n.video_studio_telegram_youtube_thread_id', '33'),
                    ],
                ],
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
     * منبع معتبر را برای سفارش تضمین می‌کند و جلوی ارسال سفارش‌های بدون فایل به n8n را می‌گیرد.
     */
    private function ensureJobSource(VideoStudioJob $job): bool
    {
        if (filled($job->source_url)) {
            return true;
        }
        if ((string) $job->source_mode !== 'auto' || !Schema::hasTable('video_studio_sources')) {
            return false;
        }
        $source = VideoStudioSource::query()
            ->where('is_active', true)
            ->whereNotNull('source_url')
            ->where('source_url', '<>', '')
            ->orderBy('used_count')
            ->orderBy('id')
            ->first();
        if (!$source) {
            return false;
        }
        $payload = is_array($job->payload) ? $job->payload : [];
        $payload['source_library_id'] = $source->id;
        $job->source_mode = $source->type;
        $job->source_url = $source->source_url;
        $job->payload = $payload;
        $job->save();
        return true;
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
            'error' => ['nullable', 'string', 'max:5000'],
            'message' => ['nullable', 'string', 'max:5000'],
        ]);

        $status = $data['status'];
        $errorMessage = trim((string) ($data['error_message'] ?? $data['error'] ?? $data['message'] ?? ''));
        if ($errorMessage !== '' && $status === 'processing') {
            $status = 'failed';
        }
        $job->update([
            'status' => $status,
            'n8n_execution_id' => $data['n8n_execution_id'] ?? $job->n8n_execution_id,
            'video_url' => $data['video_url'] ?? $job->video_url,
            'error_message' => $errorMessage !== '' ? $errorMessage : ($status === 'failed' ? $job->error_message : null),
            'started_at' => in_array($status, ['processing', 'completed'], true) ? ($job->started_at ?: now()) : $job->started_at,
            'completed_at' => in_array($status, ['completed', 'failed'], true) ? now() : null,
        ]);

        return response()->json(['ok' => true, 'job_id' => $job->id, 'status' => $job->status]);
    }
}
