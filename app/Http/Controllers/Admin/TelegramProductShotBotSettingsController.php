<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\TelegramProductDraft;
use App\Services\ProductShots\ShotVisionService;
use App\Services\TelegramProduct\ProductShotBotSettings;
use App\Support\ProviderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * داشبورد ← تنظیمات ← بات ثبت محصول ← «پروداکتی»:
 * پرامپت‌های هوش مصنوعی، پیش‌فرض اسلایدها، مدل‌های تصویر، کنترل کیفیت، نمایش و متن پیام‌های بات.
 */
class TelegramProductShotBotSettingsController extends Controller
{
    public function __construct(private readonly ProductShotBotSettings $settings)
    {
    }

    public function index(): View
    {
        $this->ensureLeader();
        $draftsReady = Schema::hasTable('telegram_product_drafts') && Schema::hasColumn('telegram_product_drafts', 'product_mode');

        return view('admin.settings.telegram-product-shot-bot', [
            'values' => $this->settings->all(),
            'defaults' => $this->settings->defaults(),
            'qualityLevels' => $this->settings->qualityLevels(),
            'aspectRatios' => (array) config('product_shots.aspect_ratios', ['4:5', '1:1', '9:16']),
            'issues' => ShotVisionService::ISSUES,
            'tiles' => ProductShotBotSettings::TILES,
            'textLabels' => ProductShotBotSettings::TEXT_LABELS,
            'models' => $this->imageModels(),
            'stats' => [
                'open' => $draftsReady ? TelegramProductDraft::query()->where('product_mode', 'product')->whereIn('state', TelegramProductDraft::ACTIVE_STATES)->count() : 0,
                'saved' => $draftsReady ? TelegramProductDraft::query()->where('product_mode', 'product')->whereIn('state', ['published', 'draft_saved'])->count() : 0,
                'published' => $draftsReady ? TelegramProductDraft::query()->where('product_mode', 'product')->where('state', 'published')->count() : 0,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->ensureLeader();
        $ratios = (array) config('product_shots.aspect_ratios', ['4:5', '1:1', '9:16']);
        $levels = array_keys($this->settings->qualityLevels());
        $rules = [
            'ai.model' => ['required', 'string', 'max:160', 'regex:/^[A-Za-z0-9._:\/-]+$/'],
            'ai.metadata_prompt' => ['required', 'string', 'min:30', 'max:12000'],
            'ai.optimizer_prompt' => ['required', 'string', 'min:30', 'max:12000'],
            'slides.max_slides' => ['required', 'integer', 'min:1', 'max:30'],
            'slides.max_prompt_chars' => ['required', 'integer', 'min:500', 'max:30000'],
            'slides.allowed_aspect_ratios' => ['required', 'array', 'min:1'],
            'slides.allowed_aspect_ratios.*' => [Rule::in($ratios)],
            'slides.aspect_ratio_default' => ['required', Rule::in($ratios)],
            'quality.blocking_issues' => ['nullable', 'array'],
            'quality.blocking_issues.*' => [Rule::in(array_keys(ShotVisionService::ISSUES))],
            'quality.min_side' => ['required', 'integer', 'min:500', 'max:3000'],
            'quality.product_sheet_size' => ['required', Rule::in([1536, 2048, 3072, '1536', '2048', '3072'])],
            'display.display_mode' => ['required', Rule::in(['card', 'slider'])],
            'display.card_shape' => ['required', Rule::in(['portrait', 'landscape', 'square'])],
            'display.gallery_layout' => ['required', Rule::in(['grid', 'masonry', 'slider'])],
            'display.card_label' => ['nullable', 'string', 'max:100'],
            'display.explore_tiles' => ['required', 'array', 'min:1'],
            'display.explore_tiles.*' => [Rule::in(array_keys(ProductShotBotSettings::TILES))],
        ];
        foreach ($levels as $level) {
            $rules["slides.credits.{$level}"] = ['required', 'integer', 'min:0', 'max:1000'];
            $rules["models.quality.{$level}.primary_id"] = ['nullable', 'integer', Rule::exists('ai_models', 'id')];
            $rules["models.quality.{$level}.fallback_ids"] = ['nullable', 'array', 'max:3'];
            $rules["models.quality.{$level}.fallback_ids.*"] = ['integer', Rule::exists('ai_models', 'id')];
        }
        foreach (array_keys(ProductShotBotSettings::TEXT_LABELS) as $key) {
            $rules["texts.{$key}"] = ['required', 'string', 'max:3000'];
        }
        $data = $request->validate($rules, [
            'ai.model.regex' => 'شناسه‌ی مدل فقط حروف انگلیسی، عدد و / - . : دارد (مثل openai/gpt-4o-mini).',
            'ai.metadata_prompt.min' => 'پرامپت مادر خیلی کوتاه است.',
            'ai.optimizer_prompt.min' => 'پرامپت اصلاح‌گر خیلی کوتاه است.',
            'slides.allowed_aspect_ratios.required' => 'حداقل یک نسبت تصویر مجاز انتخاب کنید.',
            'display.explore_tiles.required' => 'حداقل یک قاب اکسپلور انتخاب کنید.',
            'texts.*.required' => 'متن هیچ پیامی نباید خالی باشد؛ برای برگشت از دکمه‌ی «پیش‌فرض» استفاده کنید.',
        ]);

        $allowed = array_values(array_intersect($ratios, (array) data_get($data, 'slides.allowed_aspect_ratios', [])));
        $defaultRatio = (string) data_get($data, 'slides.aspect_ratio_default');
        if (! in_array($defaultRatio, $allowed, true)) {
            $defaultRatio = $allowed[0];
        }

        $this->settings->save([
            'ai' => [
                'model' => trim((string) data_get($data, 'ai.model')),
                'use_vision' => $request->boolean('ai.use_vision'),
                'metadata_prompt' => trim((string) data_get($data, 'ai.metadata_prompt')),
                'optimizer_enabled' => $request->boolean('ai.optimizer_enabled'),
                'optimizer_prompt' => trim((string) data_get($data, 'ai.optimizer_prompt')),
            ],
            'slides' => [
                'max_slides' => (int) data_get($data, 'slides.max_slides'),
                'max_prompt_chars' => (int) data_get($data, 'slides.max_prompt_chars'),
                'credits' => collect($levels)->mapWithKeys(fn ($l) => [$l => (int) data_get($data, "slides.credits.{$l}")])->all(),
                'allowed_aspect_ratios' => $allowed,
                'aspect_ratio_default' => $defaultRatio,
                'aspect_ratio_user_selectable' => $request->boolean('slides.aspect_ratio_user_selectable'),
            ],
            'models' => [
                'quality' => collect($levels)->mapWithKeys(fn ($l) => [$l => [
                    'primary_id' => ((int) data_get($data, "models.quality.{$l}.primary_id")) ?: null,
                    'fallback_ids' => array_values(array_unique(array_map('intval', (array) data_get($data, "models.quality.{$l}.fallback_ids", [])))),
                ]])->all(),
            ],
            'quality' => [
                'preflight_enabled' => $request->boolean('quality.preflight_enabled'),
                'blocking_issues' => array_values((array) data_get($data, 'quality.blocking_issues', [])),
                'min_side' => (int) data_get($data, 'quality.min_side'),
                'product_sheet_enabled' => $request->boolean('quality.product_sheet_enabled'),
                'product_sheet_size' => (int) data_get($data, 'quality.product_sheet_size'),
            ],
            'display' => [
                'watermark_enabled' => $request->boolean('display.watermark_enabled'),
                'display_mode' => (string) data_get($data, 'display.display_mode'),
                'card_shape' => (string) data_get($data, 'display.card_shape'),
                'gallery_layout' => (string) data_get($data, 'display.gallery_layout'),
                'card_label' => trim((string) data_get($data, 'display.card_label', '')) ?: 'پک',
                'explore_tiles' => array_values(array_intersect(array_keys(ProductShotBotSettings::TILES), (array) data_get($data, 'display.explore_tiles', []))),
            ],
            'texts' => collect(array_keys(ProductShotBotSettings::TEXT_LABELS))->mapWithKeys(fn ($k) => [$k => trim((string) data_get($data, "texts.{$k}"))])->all(),
        ]);

        return redirect()->route('admin.settings.telegram.product-bot.productshot')
            ->with('success', 'تنظیمات مسیر پروداکتی ذخیره شد و از ثبت بعدی در بات اعمال می‌شود.');
    }

    private function imageModels()
    {
        return AiModel::query()
            ->where('is_active', true)
            ->where('output_modality', 'image')
            ->where('supports_image_input', true)
            ->whereIn('task_type', ['image_to_image', 'face_consistency'])
            ->whereIn('provider', ProviderStatus::enabled() ?: ['__none__'])
            ->orderByDesc('featured_in_image_studio')
            ->orderBy('studio_image_priority')
            ->orderBy('provider')
            ->orderBy('name')
            ->get(['id', 'name', 'provider', 'openrouter_model_id']);
    }

    private function ensureLeader(): void
    {
        abort_unless(auth('admin')->user()?->isLeader(), 403);
    }
}
