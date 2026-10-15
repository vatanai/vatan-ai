<?php

namespace App\Services\TelegramProduct;

use App\Jobs\ProcessTelegramProductDraftJob;
use App\Models\Category;
use App\Models\Occupation;
use App\Models\Product;
use App\Models\ShotLibrary;
use App\Models\TelegramProductDraft;
use App\Models\TelegramProductManager;
use App\Models\TelegramProductRegistration;
use App\Services\OpenRouterService;
use App\Services\ProductImageOptimizer;
use App\Services\ProductShots\ProductShotFeature;
use App\Services\ProductShots\ShotProductRegistrar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * مسیر «پروداکتی» (پک شات) در بات ثبت محصول.
 *
 * فقط در حالت ارتباط مستقیم با تلگرام (بدون n8n) فعال است و مسیر پرتره را لمس نمی‌کند.
 * همه‌ی دکمه‌ها شیشه‌ای‌اند و callback آن‌ها با «ps:» شروع می‌شود.
 *
 * گام‌ها: عکس‌ها ← صنف ← شغل‌های صنف ← توضیح کوتاه ← آماده‌سازی هوشمند ← بازبینی متن
 *        ← شات‌ها (فعال / بسته‌ی آماده) ← دسته‌بندی ← خلاصه ← پیش‌نویس یا انتشار (+ قاب‌های اکسپلور)
 */
class ProductShotBotFlow
{
    public const STATES = [
        'ps_images', 'ps_group', 'ps_occupations', 'ps_brief', 'ps_review', 'ps_edit',
        'ps_shots', 'ps_categories', 'ps_confirm', 'ps_tiles',
    ];

    public const MAX_IMAGES = 8;
    private const CATEGORY_PAGE = 16;
    private const TILE_LABELS = ['1x1' => '۱×۱ مربع', '2x2' => '۲×۲ بزرگ', '1x2' => '۱×۲ عمودی', '2x1' => '۲×۱ افقی'];
    private const GLOBAL_TEXTS = ['ثبت محصول', 'ثبت محصول جدید', 'ویرایش محصول', 'تنظیمات', 'آموزش', 'لغو فرآیند'];
    private const EDIT_FIELDS = ['name_fa' => 'نام فارسی', 'name_en' => 'نام انگلیسی', 'description_fa' => 'توضیح فارسی', 'brief' => 'توضیح جدید برای بازنویسی کامل'];

    public function __construct(
        private readonly ShotProductRegistrar $registrar,
        private readonly ProductImageOptimizer $images,
        private readonly OpenRouterService $openRouter,
    ) {
    }

    /** این مسیر فقط وقتی پیشنهاد می‌شود که بات مستقیم وصل باشد و ماژول استودیو محصول نصب شده باشد. */
    public function available(array $input): bool
    {
        return ($input['transport'] ?? null) === 'direct'
            && (bool) config('product_shots.enabled', true)
            && ProductShotFeature::hasSchema()
            && Schema::hasColumn('telegram_product_drafts', 'product_mode');
    }

    /** آیا این رویداد باید به مسیر پروداکتی برود؟ */
    public function owns(?TelegramProductDraft $draft, string $eventType, array $input): bool
    {
        $callback = (string) ($input['callback_data'] ?? '');
        if ($eventType === 'callback' && Str::startsWith($callback, 'ps:')) {
            return true;
        }
        if (! $draft || ! $draft->isProductMode() || $eventType === 'start') {
            return false;
        }
        if ($eventType === 'callback') {
            return ! in_array($callback, ['product:start', 'product:settings', 'product:education', 'product:edit', 'product:cancel'], true)
                && ! Str::startsWith($callback, 'product:settings:');
        }
        if ($eventType === 'message') {
            return ! in_array(trim((string) ($input['text'] ?? '')), self::GLOBAL_TEXTS, true);
        }

        return true;
    }

    // ───────────────────────── ورود ─────────────────────────

    public function typeChoice(string $chatId): array
    {
        $text = "چه نوع محصولی می‌خواهی ثبت کنی؟\n\n"
            . "🧑 <b>پرتره (چهره‌محور)</b>\nمحصولی که کاربر عکس چهره‌ی خودش را می‌دهد؛ دقیقاً همان مسیر همیشگی.\n\n"
            . "📦 <b>پروداکتی (پک عکس محصول)</b>\nپکی برای کسب‌وکارها: مشتری عکس محصول خودش را می‌دهد و چند شات تبلیغاتی حرفه‌ای می‌گیرد.";

        return $this->response($chatId, $text, [
            [['text' => '🧑 پرتره', 'callback_data' => 'ps:type:portrait'], ['text' => '📦 پروداکتی', 'callback_data' => 'ps:type:product']],
            [['text' => 'لغو', 'callback_data' => 'ps:x']],
        ], ['status' => 'type_choice']);
    }

    public function start(TelegramProductManager $manager, string $chatId, array $input): array
    {
        if (! $manager->can('create_product')) {
            return $this->response($chatId, 'دسترسی ثبت محصول برای حساب شما فعال نیست.', [], ['status' => 'forbidden']);
        }
        TelegramProductDraft::query()
            ->where('telegram_product_manager_id', $manager->id)
            ->whereIn('state', TelegramProductDraft::ACTIVE_STATES)
            ->update(['state' => 'cancelled', 'cancelled_at' => now()]);

        $draft = TelegramProductDraft::query()->create([
            'id' => (string) Str::uuid(),
            'telegram_product_manager_id' => $manager->id,
            'telegram_id' => $manager->telegram_id,
            'chat_id' => $chatId,
            'state' => 'ps_images',
            'product_mode' => TelegramProductDraft::MODE_PRODUCT,
            'input_payload' => ['transport' => 'direct', 'pshot' => ['images' => []]],
        ]);

        return $this->imagesPrompt($draft, null);
    }

    public function handle(TelegramProductManager $manager, ?TelegramProductDraft $draft, string $chatId, array $input, string $eventType, ?UploadedFile $image): ?array
    {
        $callback = (string) ($input['callback_data'] ?? '');
        if ($callback === 'ps:x') {
            return $draft ? $this->cancel($draft, $chatId) : $this->response($chatId, 'باشه، چیزی ثبت نشد.', [], ['status' => 'cancelled']);
        }
        if ($callback === 'ps:type:product') {
            return $this->start($manager, $chatId, $input);
        }
        if (! $draft || ! $draft->isProductMode()) {
            return $this->response($chatId, 'این دکمه دیگر معتبر نیست. برای شروع «ثبت محصول» را بزنید.', [], ['status' => 'no_draft', 'callback_notice' => 'فرآیند فعالی نیست']);
        }

        return match ($eventType) {
            'photo' => $this->photo($draft, $chatId, $image),
            'callback' => $this->callback($manager, $draft, $chatId, $callback, $input),
            default => $this->text($draft, $chatId, trim((string) ($input['text'] ?? ''))),
        };
    }

    // ───────────────────────── گام ۱: عکس‌ها ─────────────────────────

    private function imagesPrompt(TelegramProductDraft $draft, ?string $notice): array
    {
        $count = count($this->data($draft)['images'] ?? []);
        $text = "📦 <b>ثبت پک پروداکتی — گام ۱ از ۵: عکس‌های نمایشی</b>\n\n"
            . "عکس‌هایی را بفرست که روی کارت و صفحه‌ی این پک نشان داده می‌شوند (نمونه‌ی خروجی‌هایی که مشتری می‌گیرد).\n"
            . "• اولین عکس، کاور کارت می‌شود.\n"
            . "• حداکثر " . $this->fa(self::MAX_IMAGES) . " عکس؛ می‌توانی چند عکس را با هم بفرستی.\n"
            . "• بهتر است عکس را «فشرده» (Photo) بفرستی، نه فایل.";
        if ($count > 0) {
            $text .= "\n\n✅ تا الان " . $this->fa($count) . " عکس دریافت شد.";
        }
        if ($notice) {
            $text .= "\n\n⚠️ {$notice}";
        }
        $rows = [];
        if ($count > 0) {
            $rows[] = [['text' => '✅ ادامه به انتخاب صنف', 'callback_data' => 'ps:img:done']];
            $rows[] = [['text' => '🗑 حذف عکس‌ها و شروع دوباره', 'callback_data' => 'ps:img:reset']];
        }
        $rows[] = [['text' => 'لغو فرآیند', 'callback_data' => 'ps:x']];

        return $this->response($draft->chat_id, $text, $rows, ['status' => 'ps_images', 'draft_id' => $draft->id]);
    }

    private function photo(TelegramProductDraft $draft, string $chatId, ?UploadedFile $image): array
    {
        if ($draft->state !== 'ps_images') {
            return $this->current($draft, 'الان منتظر عکس نیستم؛ ادامه‌ی همین مرحله را انجام بده.');
        }
        if (! $image) {
            return $this->imagesPrompt($draft, 'فایل عکس دریافت نشد؛ دوباره بفرست.');
        }

        try {
            $path = $this->images->store($image, 'products/main');
        } catch (\Throwable $exception) {
            Log::warning('telegram product shot image failed', ['draft' => $draft->id, 'error' => $exception->getMessage()]);

            return $this->imagesPrompt($draft, 'این فایل قابل خواندن نیست؛ یک عکس JPG یا PNG دیگر بفرست.');
        }

        $result = DB::transaction(function () use ($draft, $path): array {
            $locked = TelegramProductDraft::query()->whereKey($draft->id)->lockForUpdate()->firstOrFail();
            $payload = (array) $locked->input_payload;
            $images = (array) data_get($payload, 'pshot.images', []);
            if (count($images) >= self::MAX_IMAGES) {
                return ['draft' => $locked, 'stored' => false];
            }
            $images[] = $path;
            data_set($payload, 'pshot.images', array_values($images));
            $locked->forceFill(['input_payload' => $payload, 'image_paths' => array_values($images)])->save();

            return ['draft' => $locked, 'stored' => true];
        });
        if (! $result['stored']) {
            Storage::disk('public')->delete($path);

            return $this->imagesPrompt($result['draft'], 'سقف ' . $this->fa(self::MAX_IMAGES) . ' عکس پر شده است.');
        }

        return $this->imagesPrompt($result['draft'], null);
    }

    // ───────────────────────── گام ۲: صنف ─────────────────────────

    private function groupPrompt(TelegramProductDraft $draft): array
    {
        $draft->forceFill(['state' => 'ps_group'])->save();
        $selected = $this->selectedOccupations($draft);
        $groups = $this->registrar->groups();
        $selectedGroups = $this->registrar->groupsOfOccupations($selected->pluck('id')->all());
        $buttons = [];
        foreach ($groups as $key => $label) {
            $buttons[] = ['text' => (in_array($key, $selectedGroups, true) ? '✅ ' : '') . $label, 'callback_data' => 'ps:g:' . $key];
        }
        $rows = array_chunk($buttons, 2);
        if ($selected->isNotEmpty()) {
            $rows[] = [['text' => '✅ ادامه (' . $this->fa($selected->count()) . ' صنف انتخاب شده)', 'callback_data' => 'ps:g:done']];
        }
        $rows[] = [['text' => '⬅️ عکس‌ها', 'callback_data' => 'ps:back:images'], ['text' => 'لغو فرآیند', 'callback_data' => 'ps:x']];
        $text = "🏷 <b>گام ۲ از ۵: این پک برای کدام صنف است؟</b>\n\n"
            . "گروه اصلی را انتخاب کن تا شغل‌های دقیق آن را تیک بزنی. می‌توانی از چند گروه انتخاب کنی.\n"
            . "صنف تعیین می‌کند این پک به کدام کسب‌وکارها پیشنهاد شود و کدام شات‌ها در دسترس باشند.";
        if ($selected->isNotEmpty()) {
            $text .= "\n\nانتخاب‌شده: " . $selected->pluck('name_fa')->implode('، ');
        }

        return $this->response($draft->chat_id, $text, $rows, ['status' => 'ps_group', 'draft_id' => $draft->id]);
    }

    private function occupationsPrompt(TelegramProductDraft $draft, string $group): array
    {
        $occupations = $this->registrar->occupationsForGroup($group);
        if ($occupations->isEmpty()) {
            return $this->groupPrompt($draft);
        }
        $payload = (array) $draft->input_payload;
        data_set($payload, 'pshot.group', $group);
        $draft->forceFill(['state' => 'ps_occupations', 'input_payload' => $payload])->save();
        $selected = array_map('intval', (array) data_get($payload, 'pshot.occupation_ids', []));
        $buttons = $occupations->map(fn (Occupation $o) => [
            'text' => (in_array((int) $o->id, $selected, true) ? '✅ ' : '⬜ ') . $o->name_fa,
            'callback_data' => 'ps:o:' . $o->id,
        ])->all();
        $rows = array_chunk($buttons, 2);
        $allSelected = $occupations->every(fn (Occupation $o) => in_array((int) $o->id, $selected, true));
        $rows[] = [['text' => $allSelected ? '⬜ برداشتن همه' : '☑️ انتخاب همه‌ی این گروه', 'callback_data' => 'ps:o:all']];
        $rows[] = [['text' => '➕ گروه دیگر', 'callback_data' => 'ps:back:group'], ['text' => '✅ ادامه', 'callback_data' => 'ps:g:done']];
        $rows[] = [['text' => 'لغو فرآیند', 'callback_data' => 'ps:x']];
        $label = Occupation::GROUPS[$group] ?? $group;

        return $this->response($draft->chat_id, "🏷 <b>{$label}</b>\n\nشغل‌هایی که این پک برایشان مناسب است را تیک بزن:", $rows, [
            'status' => 'ps_occupations',
            'draft_id' => $draft->id,
        ]);
    }

    private function toggleOccupation(TelegramProductDraft $draft, string $value): array
    {
        $payload = (array) $draft->input_payload;
        $group = (string) data_get($payload, 'pshot.group', '');
        $selected = array_map('intval', (array) data_get($payload, 'pshot.occupation_ids', []));
        $groupIds = $this->registrar->occupationsForGroup($group)->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($value === 'all') {
            $selected = array_diff($groupIds, $selected) === []
                ? array_values(array_diff($selected, $groupIds))
                : array_values(array_unique(array_merge($selected, $groupIds)));
        } else {
            $id = (int) $value;
            if (in_array($id, $groupIds, true)) {
                $selected = in_array($id, $selected, true) ? array_values(array_diff($selected, [$id])) : [...$selected, $id];
            }
        }
        data_set($payload, 'pshot.occupation_ids', array_values(array_unique($selected)));
        $draft->forceFill(['input_payload' => $payload])->save();

        return $this->occupationsPrompt($draft->fresh(), $group);
    }

    // ───────────────────────── گام ۳: توضیح ─────────────────────────

    private function briefPrompt(TelegramProductDraft $draft, ?string $notice = null): array
    {
        $draft->forceFill(['state' => 'ps_brief'])->save();
        $text = "✍️ <b>گام ۳ از ۵: یک توضیح کوتاه درباره‌ی این پک بنویس</b>\n\n"
            . "در یک پیام بنویس (فارسی کافی است):\n"
            . "• این پک برای چه محصولی است؟ (مثلاً: سرم پوست در بطری شیشه‌ای، کفش ورزشی، انگشتر طلا)\n"
            . "• حس و سبک دلخواه (مثلاً: لوکس و تمیز، شاد و رنگی، طبیعی و گرم)\n"
            . "• اگر نام یا رنگ برند خاصی مدنظر است، بنویس.\n\n"
            . "نام فارسی و انگلیسی، توضیح، هشتگ، دسته‌بندی و پیشنهاد شات‌ها را هوش مصنوعی آماده می‌کند و تو بازبینی می‌کنی.\n\n"
            . "مثال: «پک تبلیغاتی سرم ویتامین C در بطری قطره‌چکان شیشه‌ای نارنجی؛ حس تازه و آفتابی با پرتقال و قطره‌های آب؛ مناسب پست و استوری فروشگاه آرایشی»";
        if ($notice) {
            $text .= "\n\n⚠️ {$notice}";
        }

        return $this->response($draft->chat_id, $text, [
            [['text' => '⬅️ صنف', 'callback_data' => 'ps:back:group'], ['text' => 'لغو فرآیند', 'callback_data' => 'ps:x']],
        ], ['status' => 'ps_brief', 'draft_id' => $draft->id]);
    }

    private function receiveBrief(TelegramProductDraft $draft, string $text): array
    {
        if (mb_strlen($text) < 15) {
            return $this->briefPrompt($draft, 'توضیح خیلی کوتاه است؛ کمی کامل‌تر بنویس (حداقل یک جمله).');
        }

        return $this->beginProcessing($draft, Str::limit($text, 2000, ''));
    }

    private function beginProcessing(TelegramProductDraft $draft, string $brief): array
    {
        $draft->forceFill([
            'description' => $brief,
            'state' => 'processing',
            'pending_edit_field' => null,
            'error_message' => null,
            'processing_started_at' => now(),
            'reviewed_at' => null,
        ])->save();
        ProcessTelegramProductDraftJob::dispatch($draft->id)->afterCommit();

        return $this->response($draft->chat_id, "⏳ <b>در حال آماده‌سازی هوشمند…</b>\n\nنام، توضیح، هشتگ‌ها، دسته‌بندی و شات‌های پیشنهادی در حال ساخت است. معمولاً کمتر از یک دقیقه طول می‌کشد و نتیجه خودش برایت می‌آید.", [
            [['text' => '🔄 بررسی وضعیت', 'callback_data' => 'ps:status'], ['text' => 'لغو فرآیند', 'callback_data' => 'ps:x']],
        ], ['status' => 'processing', 'draft_id' => $draft->id]);
    }

    // ───────────────────────── پردازش هوشمند (در صف) ─────────────────────────

    public function process(TelegramProductDraft $draft): void
    {
        $draft->refresh();
        if ($draft->state !== 'processing') {
            return;
        }

        try {
            $occupations = $this->selectedOccupations($draft);
            $groups = $this->registrar->groupsOfOccupations($occupations->pluck('id')->all());
            $shots = $this->registrar->shotsForGroups($groups);
            $categories = Category::query()->active()->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'name_fa']);
            $ai = $this->generateMetadata($draft, $occupations, $groups, $shots, $categories);

            $category = $this->matchCategory((string) ($ai['category'] ?? ''), $categories);
            $payload = (array) $draft->input_payload;
            $current = (array) data_get($payload, 'pshot', []);
            // انتخاب‌های دستی قبلی (در بازنویسی) حفظ می‌شوند.
            if (empty($current['shot_ids'])) {
                $defaults = $this->registrar->suggestDefaults($shots, (array) ($ai['suggested_shots'] ?? []));
                // فعال پیش‌فرض: بسته‌ی آماده + همه‌ی شات‌های تخصصی همان صنف (انتخاب بیشتر برای مشتری).
                $specialized = $shots->reject(fn (ShotLibrary $s) => Str::startsWith($s->key, ShotProductRegistrar::GENERAL_PREFIX))->pluck('id')->map(fn ($id) => (int) $id)->all();
                $order = $shots->pluck('id')->map(fn ($id) => (int) $id)->all();
                $current['shot_ids'] = array_values(array_intersect($order, array_unique([...$defaults, ...$specialized])));
                $current['default_ids'] = array_values(array_intersect($order, $defaults));
            }
            if (! array_key_exists('category_ids', $current)) {
                $current['category_ids'] = $category ? [(int) $category->id] : [];
            }
            $payload['pshot'] = $current;

            $duplicate = $this->duplicateProduct((string) $ai['name_fa'], (string) $ai['name_en']);
            $ai['category_id'] = $category?->id;
            $ai['category_name'] = $category ? ($category->name_fa ?: $category->name) : null;
            $ai['duplicate_product_name'] = $duplicate ? ($duplicate->name_fa ?: $duplicate->name_en) : null;
            $ai['duplicate_product_code'] = $duplicate?->product_code;

            $draft->forceFill([
                'ai_result' => $ai,
                'input_payload' => $payload,
                'state' => 'ps_review',
                'error_message' => null,
                'reviewed_at' => null,
            ])->save();
        } catch (\Throwable $exception) {
            Log::warning('telegram product shot metadata failed', ['draft' => $draft->id, 'error' => $exception->getMessage()]);
            $draft->forceFill([
                'state' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 1000, ''),
            ])->save();
        }
    }

    private function generateMetadata(TelegramProductDraft $draft, Collection $occupations, array $groups, Collection $shots, Collection $categories): array
    {
        $groupLabels = collect($groups)->map(fn ($g) => Occupation::GROUPS[$g] ?? $g)->implode('، ');
        $shotList = $shots->map(fn (ShotLibrary $s) => "{$s->key}: {$s->name_fa} ({$s->category})")->implode("\n");
        $categoryList = $categories->map(fn (Category $c) => $c->name_fa ?: $c->name)->filter()->implode('، ');
        $pack = $this->registrar->defaultPackSize();
        $instruction = "تو کارشناس محتوای فروشگاهی و عکاسی تبلیغاتی محصول هستی. مدیر یک «پک عکس تبلیغاتی» در پلتفرم وطن ثبت می‌کند: "
            . "مشتری (یک کسب‌وکار) عکس محصول خودش را می‌دهد و این پک چند شات تبلیغاتی حرفه‌ای از همان محصول می‌سازد.\n\n"
            . "توضیح مدیر:\n{$draft->description}\n\n"
            . "صنف‌ها: {$groupLabels}\nشغل‌ها: " . $occupations->pluck('name_fa')->implode('، ') . "\n\n"
            . "فهرست شات‌های مجاز (کلید: نام):\n{$shotList}\n\n"
            . "دسته‌بندی‌های مجاز سایت:\n{$categoryList}\n\n"
            . "اگر تصویری پیوست شده، نمونه‌ی خروجی یا محصول این پک است؛ از آن برای توصیف دقیق‌تر استفاده کن.\n\n"
            . "فقط یک JSON معتبر با دقیقاً این کلیدها برگردان:\n"
            . "name_fa: نام کوتاه و جذاب فارسی پک (حداکثر ۶ کلمه، بدون کلمه‌ی «پک» در ابتدا اگر لازم نیست)\n"
            . "name_en: نام انگلیسی کوتاه\n"
            . "description_fa: توضیح فارسی ۲ تا ۳ جمله برای مشتری: چه می‌گیرد و برای کجا مناسب است\n"
            . "tags: آرایه‌ی حداکثر ۸ هشتگ فارسی بدون #\n"
            . "product_description: یک جمله‌ی انگلیسی حداکثر ۲۵۰ کاراکتر از نوع و شکل فیزیکی محصول (برای مدل تصویر؛ بدون نام برند)\n"
            . "brand_style: عبارت انگلیسی کوتاه برای حس و سبک (مثلاً clean luxury, warm natural)\n"
            . "brand_palette: عبارت انگلیسی کوتاه برای پالت رنگی یا خالی\n"
            . "category: دقیقاً یکی از دسته‌بندی‌های مجاز\n"
            . "suggested_shots: آرایه‌ای از {$pack} کلید از فهرست شات‌های مجاز که متنوع‌ترین و مناسب‌ترین بسته‌ی آماده را بسازند";

        $model = (string) config('services.telegram_product.ai_model', 'openai/gpt-4o-mini');
        $image = $this->firstImageDataUrl($draft);
        $result = null;
        if ($image) {
            try {
                $result = $this->openRouter->analyzeImagesJson($model, $instruction, [$image], 60);
            } catch (\Throwable $exception) {
                Log::info('telegram product shot vision metadata fallback to text', ['draft' => $draft->id, 'error' => $exception->getMessage()]);
            }
        }
        $result ??= $this->openRouter->generateStructuredText('فقط JSON معتبر برگردان.', $instruction, $model, 60);
        $ai = (array) ($result['content'] ?? []);

        foreach (['name_fa', 'name_en', 'description_fa'] as $key) {
            if (trim((string) ($ai[$key] ?? '')) === '') {
                throw new \RuntimeException("فیلد {$key} در پاسخ هوش مصنوعی خالی است.");
            }
        }

        return [
            'name_fa' => Str::limit(trim((string) $ai['name_fa']), 120, ''),
            'name_en' => Str::limit(trim((string) $ai['name_en']), 120, ''),
            'description_fa' => Str::limit(trim((string) $ai['description_fa']), 1500, ''),
            'tags' => collect((array) ($ai['tags'] ?? []))->map(fn ($t) => trim(ltrim((string) $t, '#')))->filter()->unique()->take(8)->values()->all(),
            'product_description' => Str::limit(trim((string) ($ai['product_description'] ?? '')), 300, ''),
            'brand_style' => Str::limit(trim((string) ($ai['brand_style'] ?? '')), 200, ''),
            'brand_palette' => Str::limit(trim((string) ($ai['brand_palette'] ?? '')), 120, ''),
            'category' => trim((string) ($ai['category'] ?? '')),
            'suggested_shots' => array_values(array_filter(array_map('strval', (array) ($ai['suggested_shots'] ?? [])))),
            'model' => (string) ($result['model'] ?? $model),
        ];
    }

    private function firstImageDataUrl(TelegramProductDraft $draft): ?string
    {
        $path = collect((array) data_get($draft->input_payload, 'pshot.images', []))->first();
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }
        $bytes = Storage::disk('public')->get($path);
        if (! $bytes || strlen($bytes) > 4 * 1024 * 1024) {
            return null;
        }
        $mime = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';

        return 'data:' . $mime . ';base64,' . base64_encode($bytes);
    }

    /** پیام بعد از اتمام پردازش (برای ارسال خودکار یا دکمه‌ی بررسی وضعیت). */
    public function resultResponse(TelegramProductDraft $draft): array
    {
        $draft->refresh();

        return match ($draft->state) {
            'processing' => $this->response($draft->chat_id, '⏳ هنوز در حال آماده‌سازی است؛ چند لحظه‌ی دیگر خودش می‌رسد.', [
                [['text' => '🔄 بررسی وضعیت', 'callback_data' => 'ps:status'], ['text' => 'لغو فرآیند', 'callback_data' => 'ps:x']],
            ], ['status' => 'processing', 'draft_id' => $draft->id, 'callback_notice' => 'هنوز آماده نیست']),
            'failed' => $this->failed($draft),
            'ps_review' => $this->review($draft),
            default => $this->current($draft, null),
        };
    }

    private function failed(TelegramProductDraft $draft): array
    {
        return $this->response($draft->chat_id, "⚠️ آماده‌سازی هوشمند با خطا روبه‌رو شد.\n\nعکس‌ها و انتخاب‌هایت حفظ شده‌اند. «تلاش مجدد» را بزن یا یک توضیح تازه بفرست.", [
            [['text' => '🔁 تلاش مجدد', 'callback_data' => 'ps:retry'], ['text' => '✍️ توضیح تازه', 'callback_data' => 'ps:back:brief']],
            [['text' => 'لغو فرآیند', 'callback_data' => 'ps:x']],
        ], ['status' => 'failed', 'draft_id' => $draft->id]);
    }

    // ───────────────────────── گام ۴: بازبینی ─────────────────────────

    private function review(TelegramProductDraft $draft, ?string $notice = null): array
    {
        if ($draft->state !== 'ps_review') {
            $draft->forceFill(['state' => 'ps_review', 'pending_edit_field' => null])->save();
        }
        $ai = (array) $draft->ai_result;
        $tags = collect((array) ($ai['tags'] ?? []))->map(fn ($t) => '#' . str_replace([' ', '‌'], '_', $t))->implode(' ');
        $text = "🧾 <b>گام ۴ از ۵: بازبینی اطلاعات پک</b>\n\n"
            . "<b>نام:</b> " . $this->e($ai['name_fa'] ?? '') . "\n"
            . "<b>نام انگلیسی:</b> " . $this->e($ai['name_en'] ?? '') . "\n\n"
            . "<b>توضیح:</b>\n" . $this->e($ai['description_fa'] ?? '') . "\n\n"
            . "<b>صنف:</b> " . $this->e($this->selectedOccupations($draft)->pluck('name_fa')->implode('، ') ?: '—') . "\n"
            . "<b>دسته‌بندی:</b> " . $this->e($this->selectedCategories($draft)->map(fn ($c) => $c->name_fa ?: $c->name)->implode('، ') ?: '— (در مرحله‌ی بعد انتخاب کن)') . "\n"
            . "<b>هشتگ‌ها:</b> " . $this->e($tags ?: '—') . "\n\n"
            . "<b>برای مدل تصویر:</b>\n"
            . "محصول: " . $this->e(($ai['product_description'] ?? '') ?: '—') . "\n"
            . "سبک: " . $this->e(($ai['brand_style'] ?? '') ?: '—') . ' • پالت: ' . $this->e(($ai['brand_palette'] ?? '') ?: '—');
        if (! empty($ai['duplicate_product_name'])) {
            $text .= "\n\n⚠️ محصولی با نام مشابه وجود دارد: " . $this->e($ai['duplicate_product_name'])
                . (! empty($ai['duplicate_product_code']) ? ' (کد ' . $ai['duplicate_product_code'] . ')' : '')
                . "\nاگر پک جدیدی است ادامه بده؛ وگرنه نام را اصلاح کن.";
        }
        if ($notice) {
            $text .= "\n\n✅ {$notice}";
        }

        return $this->response($draft->chat_id, $text, [
            [['text' => '✅ تأیید و رفتن به شات‌ها', 'callback_data' => 'ps:rv:ok']],
            [['text' => '✏️ نام', 'callback_data' => 'ps:ed:name_fa'], ['text' => '✏️ نام انگلیسی', 'callback_data' => 'ps:ed:name_en']],
            [['text' => '✏️ توضیح', 'callback_data' => 'ps:ed:description_fa'], ['text' => '🔁 بازنویسی کامل', 'callback_data' => 'ps:ed:brief']],
            [['text' => 'لغو فرآیند', 'callback_data' => 'ps:x']],
        ], ['status' => 'ps_review', 'draft_id' => $draft->id, 'photo_url' => $this->coverUrl($draft)]);
    }

    private function editPrompt(TelegramProductDraft $draft, string $field): array
    {
        if (! array_key_exists($field, self::EDIT_FIELDS)) {
            return $this->review($draft);
        }
        $draft->forceFill(['state' => 'ps_edit', 'pending_edit_field' => $field])->save();
        $ai = (array) $draft->ai_result;
        $current = $field === 'brief' ? (string) $draft->description : (string) ($ai[$field] ?? '');
        $text = "✏️ <b>" . self::EDIT_FIELDS[$field] . "</b>\n\nمتن فعلی (برای کپی):\n<code>" . $this->e($current) . "</code>\n\n👇 متن جدید را در یک پیام بفرست.";
        if ($field === 'brief') {
            $text .= "\nبا این متن، همه‌چیز دوباره و از نو آماده می‌شود.";
        }

        return $this->response($draft->chat_id, $text, [
            [['text' => '⬅️ انصراف از اصلاح', 'callback_data' => 'ps:rv:back']],
        ], ['status' => 'ps_edit', 'draft_id' => $draft->id]);
    }

    private function applyEdit(TelegramProductDraft $draft, string $text): array
    {
        $field = (string) $draft->pending_edit_field;
        if ($text === '') {
            return $this->editPrompt($draft, $field);
        }
        if ($field === 'brief') {
            return mb_strlen($text) < 15
                ? $this->editPrompt($draft, $field)
                : $this->beginProcessing($draft, Str::limit($text, 2000, ''));
        }
        $limit = $field === 'description_fa' ? 1500 : 120;
        $ai = (array) $draft->ai_result;
        $ai[$field] = Str::limit($text, $limit, '');
        if (in_array($field, ['name_fa', 'name_en'], true)) {
            $duplicate = $this->duplicateProduct((string) ($ai['name_fa'] ?? ''), (string) ($ai['name_en'] ?? ''));
            $ai['duplicate_product_name'] = $duplicate ? ($duplicate->name_fa ?: $duplicate->name_en) : null;
            $ai['duplicate_product_code'] = $duplicate?->product_code;
        }
        $draft->forceFill(['ai_result' => $ai, 'state' => 'ps_review', 'pending_edit_field' => null])->save();

        return $this->review($draft, self::EDIT_FIELDS[$field] . ' به‌روز شد.');
    }

    // ───────────────────────── گام ۵: شات‌ها ─────────────────────────

    private function shotsPrompt(TelegramProductDraft $draft, ?string $notice = null): array
    {
        $draft->forceFill(['state' => 'ps_shots'])->save();
        $data = $this->data($draft);
        $mode = ($data['shot_mode'] ?? 'enable') === 'default' ? 'default' : 'enable';
        $shots = $this->availableShots($draft);
        $enabled = array_map('intval', (array) ($data['shot_ids'] ?? []));
        $defaults = array_map('intval', (array) ($data['default_ids'] ?? []));
        $packCredits = $shots->whereIn('id', $defaults)->sum(fn (ShotLibrary $s) => $this->registrar->shotCredits($s));
        $minCredits = $shots->whereIn('id', $enabled)->map(fn (ShotLibrary $s) => $this->registrar->shotCredits($s))->min();

        $rows = [];
        foreach ($shots as $shot) {
            $isOn = in_array((int) $shot->id, $enabled, true);
            $isDefault = in_array((int) $shot->id, $defaults, true);
            $mark = $isOn ? ($isDefault ? '⭐' : '✅') : '⬜';
            $rows[] = [['text' => Str::limit("{$mark} {$shot->name_fa} · " . $this->fa($this->registrar->shotCredits($shot)) . ' کردیت', 60, '…'), 'callback_data' => 'ps:s:' . $shot->id]];
        }
        $rows[] = [
            ['text' => $mode === 'enable' ? '🔀 حالت: انتخاب شات‌های فعال' : '🔀 حالت: انتخاب بسته‌ی آماده ⭐', 'callback_data' => 'ps:s:mode'],
        ];
        $rows[] = [['text' => '✨ پیشنهاد هوشمند', 'callback_data' => 'ps:s:smart'], ['text' => '🖼 نمونه‌ی شات‌ها', 'callback_data' => 'ps:s:samples']];
        $rows[] = [['text' => '✅ ادامه', 'callback_data' => 'ps:s:done']];
        $rows[] = [['text' => '⬅️ بازبینی', 'callback_data' => 'ps:rv:back'], ['text' => 'لغو فرآیند', 'callback_data' => 'ps:x']];

        $text = "🎯 <b>گام ۵ از ۵: شات‌های این پک</b>\n\n"
            . "✅ <b>فعال</b>: شات در پک هست و مشتری می‌تواند انتخابش کند.\n"
            . "⭐ <b>بسته‌ی آماده</b>: شات از قبل برای مشتری تیک خورده است (مشتری فقط «بساز» می‌زند).\n\n"
            . ($mode === 'enable'
                ? "👆 حالت فعلی: با زدن هر شات، آن را <b>فعال/غیرفعال</b> می‌کنی."
                : "👆 حالت فعلی: با زدن هر شات، آن را <b>به بسته‌ی آماده اضافه/حذف</b> می‌کنی (حداکثر " . $this->fa($this->registrar->maxPackSize()) . ').')
            . "\n\nفعال: " . $this->fa(count($enabled)) . ' شات'
            . ' • بسته‌ی آماده: ' . $this->fa(count($defaults)) . ' شات = ' . $this->fa((int) $packCredits) . ' کردیت'
            . ($minCredits !== null ? "\nقیمت روی کارت: از " . $this->fa((int) $minCredits) . ' کردیت' : '')
            . "\n\nقیمت، نمونه‌عکس و تنظیمات پیشرفته‌ی هر شات بعداً از پنل «استودیو محصول» قابل تغییر است.";
        if ($notice) {
            $text .= "\n\n⚠️ {$notice}";
        }

        return $this->response($draft->chat_id, $text, $rows, ['status' => 'ps_shots', 'draft_id' => $draft->id]);
    }

    private function shotsAction(TelegramProductDraft $draft, string $action): array
    {
        $payload = (array) $draft->input_payload;
        $data = (array) ($payload['pshot'] ?? []);
        $enabled = array_map('intval', (array) ($data['shot_ids'] ?? []));
        $defaults = array_map('intval', (array) ($data['default_ids'] ?? []));
        $shots = $this->availableShots($draft);
        $notice = null;

        if ($action === 'mode') {
            $data['shot_mode'] = ($data['shot_mode'] ?? 'enable') === 'default' ? 'enable' : 'default';
        } elseif ($action === 'smart') {
            $defaults = $this->registrar->suggestDefaults($shots, (array) data_get($draft->ai_result, 'suggested_shots', []));
            $enabled = array_values(array_unique(array_merge($enabled, $defaults)));
        } elseif ($action === 'samples') {
            return $this->shotSamples($draft, $shots);
        } elseif ($action === 'done') {
            if ($enabled === []) {
                return $this->shotsPrompt($draft, 'حداقل یک شات را فعال کن.');
            }
            if ($defaults === []) {
                return $this->shotsPrompt($draft, 'حداقل یک شات را با ⭐ در بسته‌ی آماده بگذار (حالت را عوض کن و شات را بزن).');
            }

            return $this->categoriesPrompt($draft, 0);
        } else {
            $id = (int) $action;
            if ($shots->contains('id', $id)) {
                if (($data['shot_mode'] ?? 'enable') === 'default') {
                    if (in_array($id, $defaults, true)) {
                        $defaults = array_values(array_diff($defaults, [$id]));
                    } elseif (count($defaults) >= $this->registrar->maxPackSize()) {
                        $notice = 'بسته‌ی آماده حداکثر ' . $this->fa($this->registrar->maxPackSize()) . ' شات دارد؛ اول یکی را بردار.';
                    } else {
                        $defaults[] = $id;
                        $enabled = array_values(array_unique([...$enabled, $id]));
                    }
                } elseif (in_array($id, $enabled, true)) {
                    $enabled = array_values(array_diff($enabled, [$id]));
                    $defaults = array_values(array_diff($defaults, [$id]));
                } else {
                    $enabled[] = $id;
                }
            }
        }

        // ترتیب ثابت = ترتیب کتابخانه (برای نمایش یکدست به مشتری).
        $order = $shots->pluck('id')->map(fn ($id) => (int) $id)->all();
        $data['shot_ids'] = array_values(array_intersect($order, $enabled));
        $data['default_ids'] = array_values(array_intersect($order, $defaults));
        $payload['pshot'] = $data;
        $draft->forceFill(['input_payload' => $payload])->save();

        return $this->shotsPrompt($draft->fresh(), $notice);
    }

    private function shotSamples(TelegramProductDraft $draft, Collection $shots): array
    {
        $media = $shots->map(fn (ShotLibrary $s) => ['url' => $s->sampleImageUrl(), 'caption' => $s->name_fa])
            ->filter(fn ($m) => filled($m['url']))
            ->take(10)
            ->values()
            ->all();
        if ($media === []) {
            return $this->shotsPrompt($draft, 'هنوز برای این شات‌ها نمونه‌عکسی ثبت نشده است؛ از پنل «استودیو محصول» می‌توانی نمونه بسازی.');
        }

        return array_merge($this->shotsPrompt($draft), ['media' => $media, 'callback_notice' => 'نمونه‌ها ارسال شد']);
    }

    // ───────────────────────── دسته‌بندی ─────────────────────────

    private function categoriesPrompt(TelegramProductDraft $draft, int $page, ?string $notice = null): array
    {
        $payload = (array) $draft->input_payload;
        data_set($payload, 'pshot.category_page', $page);
        $draft->forceFill(['state' => 'ps_categories', 'input_payload' => $payload])->save();
        $all = Category::query()->active()->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'name_fa', 'parent_id']);
        $selected = array_map('intval', (array) data_get($payload, 'pshot.category_ids', []));
        $pages = max(1, (int) ceil($all->count() / self::CATEGORY_PAGE));
        $page = max(0, min($page, $pages - 1));
        $buttons = $all->slice($page * self::CATEGORY_PAGE, self::CATEGORY_PAGE)->map(fn (Category $c) => [
            'text' => (in_array((int) $c->id, $selected, true) ? '✅ ' : '⬜ ') . Str::limit((string) ($c->name_fa ?: $c->name), 30, '…'),
            'callback_data' => 'ps:c:' . $c->id,
        ])->values()->all();
        $rows = array_chunk($buttons, 2);
        if ($pages > 1) {
            $nav = [];
            if ($page > 0) {
                $nav[] = ['text' => '▶️ قبلی', 'callback_data' => 'ps:cp:' . ($page - 1)];
            }
            $nav[] = ['text' => $this->fa($page + 1) . ' از ' . $this->fa($pages), 'callback_data' => 'ps:cp:' . $page];
            if ($page < $pages - 1) {
                $nav[] = ['text' => 'بعدی ◀️', 'callback_data' => 'ps:cp:' . ($page + 1)];
            }
            $rows[] = $nav;
        }
        $rows[] = [['text' => '✅ ادامه به خلاصه', 'callback_data' => 'ps:c:done']];
        $rows[] = [['text' => '⬅️ شات‌ها', 'callback_data' => 'ps:back:shots'], ['text' => 'لغو فرآیند', 'callback_data' => 'ps:x']];
        $names = $all->whereIn('id', $selected)->map(fn ($c) => $c->name_fa ?: $c->name)->implode('، ');
        $text = "🗂 <b>گام آخر: دسته‌بندی پک در سایت</b>\n\nپیشنهاد هوش مصنوعی از قبل تیک خورده است. برای انتشار حداقل یک دسته لازم است.\n\nانتخاب‌شده: " . ($names ?: '—');
        if ($notice) {
            $text .= "\n\n⚠️ {$notice}";
        }

        return $this->response($draft->chat_id, $text, $rows, ['status' => 'ps_categories', 'draft_id' => $draft->id]);
    }

    private function toggleCategory(TelegramProductDraft $draft, int $id): array
    {
        $payload = (array) $draft->input_payload;
        $selected = array_map('intval', (array) data_get($payload, 'pshot.category_ids', []));
        if (Category::query()->active()->whereKey($id)->exists()) {
            $selected = in_array($id, $selected, true) ? array_values(array_diff($selected, [$id])) : [...$selected, $id];
        }
        data_set($payload, 'pshot.category_ids', array_values(array_unique($selected)));
        $draft->forceFill(['input_payload' => $payload])->save();

        return $this->categoriesPrompt($draft->fresh(), (int) data_get($payload, 'pshot.category_page', 0));
    }

    // ───────────────────────── خلاصه و ثبت ─────────────────────────

    private function confirmPrompt(TelegramProductDraft $draft, TelegramProductManager $manager, ?string $notice = null): array
    {
        $draft->forceFill(['state' => 'ps_confirm', 'reviewed_at' => now()])->save();
        $ai = (array) $draft->ai_result;
        $data = $this->data($draft);
        $shots = $this->availableShots($draft);
        $enabled = $shots->whereIn('id', array_map('intval', (array) ($data['shot_ids'] ?? [])));
        $defaults = $shots->whereIn('id', array_map('intval', (array) ($data['default_ids'] ?? [])));
        $packCredits = $defaults->sum(fn (ShotLibrary $s) => $this->registrar->shotCredits($s));
        $moduleOn = app(ProductShotFeature::class)->enabled();

        $text = "📋 <b>خلاصه‌ی پک، آماده‌ی ثبت</b>\n\n"
            . "<b>" . $this->e($ai['name_fa'] ?? '') . "</b> — " . $this->e($ai['name_en'] ?? '') . "\n"
            . $this->e(Str::limit((string) ($ai['description_fa'] ?? ''), 300)) . "\n\n"
            . "🏷 صنف: " . $this->e($this->selectedOccupations($draft)->pluck('name_fa')->implode('، ')) . "\n"
            . "🗂 دسته: " . $this->e($this->selectedCategories($draft)->map(fn ($c) => $c->name_fa ?: $c->name)->implode('، ') ?: '—') . "\n"
            . "🖼 عکس‌ها: " . $this->fa(count((array) ($data['images'] ?? []))) . "\n"
            . "🎯 شات‌های فعال (" . $this->fa($enabled->count()) . "): " . $this->e($enabled->pluck('name_fa')->implode('، ')) . "\n"
            . "⭐ بسته‌ی آماده (" . $this->fa($defaults->count()) . "): " . $this->e($defaults->pluck('name_fa')->implode('، ')) . "\n"
            . "💳 قیمت بسته‌ی آماده: " . $this->fa((int) $packCredits) . " کردیت\n\n"
            . "مدل‌های کیفیت، فیلتر کیفیت عکس مشتری و کنترل کیفیت خروجی با تنظیمات پیش‌فرض استودیو محصول فعال می‌شوند.";
        if (! $moduleOn) {
            $text .= "\n\nℹ️ «استودیو محصول» در سایت هنوز خاموش است؛ پک ثبت می‌شود ولی تا روشن کردن آن از پنل، برای کاربران دیده نمی‌شود.";
        }
        if ($notice) {
            $text .= "\n\n⚠️ {$notice}";
        }
        $rows = [];
        $first = [['text' => '💾 ذخیره در پیش‌نویس', 'callback_data' => 'ps:save:draft']];
        if ($manager->can('publish_product')) {
            $first[] = ['text' => '🚀 انتشار در سایت', 'callback_data' => 'ps:save:active'];
        }
        $rows[] = $first;
        $rows[] = [['text' => '✏️ متن', 'callback_data' => 'ps:rv:back'], ['text' => '✏️ شات‌ها', 'callback_data' => 'ps:back:shots'], ['text' => '✏️ دسته', 'callback_data' => 'ps:back:categories']];
        $rows[] = [['text' => 'لغو فرآیند', 'callback_data' => 'ps:x']];

        return $this->response($draft->chat_id, $text, $rows, ['status' => 'ps_confirm', 'draft_id' => $draft->id, 'photo_url' => $this->coverUrl($draft)]);
    }

    private function tilesPrompt(TelegramProductDraft $draft, ?string $notice = null): array
    {
        $draft->forceFill(['state' => 'ps_tiles'])->save();
        $selected = $this->selectedTiles($draft);
        $buttons = [];
        foreach (self::TILE_LABELS as $key => $label) {
            $buttons[] = ['text' => (in_array($key, $selected, true) ? '✅ ' : '⬜ ') . $label, 'callback_data' => 'ps:t:' . $key];
        }
        $rows = array_chunk($buttons, 2);
        $rows[] = [['text' => '🚀 ثبت و انتشار', 'callback_data' => 'ps:t:done']];
        $rows[] = [['text' => '⬅️ خلاصه', 'callback_data' => 'ps:back:confirm'], ['text' => 'لغو فرآیند', 'callback_data' => 'ps:x']];
        $text = "🖼 <b>قاب نمایش در هوم و اکسپلور</b>\n\nقاب‌هایی که این پک اجازه‌ی نمایش در آن‌ها را دارد روشن کن (حداقل یکی).";
        if ($notice) {
            $text .= "\n\n⚠️ {$notice}";
        }

        return $this->response($draft->chat_id, $text, $rows, ['status' => 'ps_tiles', 'draft_id' => $draft->id]);
    }

    private function toggleTile(TelegramProductDraft $draft, string $key): array
    {
        $selected = $this->selectedTiles($draft);
        $notice = null;
        if (array_key_exists($key, self::TILE_LABELS)) {
            if (in_array($key, $selected, true)) {
                if (count($selected) === 1) {
                    $notice = 'حداقل یک قاب باید روشن بماند.';
                } else {
                    $selected = array_values(array_diff($selected, [$key]));
                }
            } else {
                $selected[] = $key;
            }
        }
        $payload = (array) $draft->input_payload;
        data_set($payload, 'pshot.tiles', array_values(array_intersect(array_keys(self::TILE_LABELS), $selected)));
        $draft->forceFill(['input_payload' => $payload])->save();

        return $this->tilesPrompt($draft->fresh(), $notice);
    }

    private function save(TelegramProductDraft $draft, TelegramProductManager $manager, string $status): array
    {
        if (! in_array($draft->state, ['ps_confirm', 'ps_tiles'], true)) {
            return $this->current($draft, 'این مرحله دیگر برای ثبت آماده نیست.');
        }
        if ($status === 'active' && ! $manager->can('publish_product')) {
            return $this->confirmPrompt($draft, $manager, 'دسترسی انتشار برای حساب شما فعال نیست؛ می‌توانی در پیش‌نویس ذخیره کنی.');
        }
        if ($status === 'active' && $this->selectedCategories($draft)->isEmpty()) {
            return $this->categoriesPrompt($draft, 0, 'برای انتشار حداقل یک دسته‌بندی انتخاب کن.');
        }

        $ai = (array) $draft->ai_result;
        $data = $this->data($draft);
        $order = array_map('intval', (array) ($data['shot_ids'] ?? []));
        $defaults = array_map('intval', (array) ($data['default_ids'] ?? []));
        $shots = [];
        foreach ($order as $index => $id) {
            $shots[$id] = ['enabled' => true, 'is_default' => in_array($id, $defaults, true), 'sort' => $index];
        }

        try {
            $product = $this->registrar->create([
                'name_fa' => (string) ($ai['name_fa'] ?? ''),
                'name_en' => (string) ($ai['name_en'] ?? ''),
                'description_fa' => (string) ($ai['description_fa'] ?? ''),
                'tags' => (array) ($ai['tags'] ?? []),
                'status' => $status,
                'occupation_ids' => (array) ($data['occupation_ids'] ?? []),
                'category_ids' => (array) ($data['category_ids'] ?? []),
                'product_description' => (string) ($ai['product_description'] ?? ''),
                'brand_style' => (string) ($ai['brand_style'] ?? ''),
                'brand_palette' => (string) ($ai['brand_palette'] ?? ''),
                'image_paths' => (array) ($data['images'] ?? []),
                'shots' => $shots,
                'explore_tiles' => $this->selectedTiles($draft),
                'created_by' => $manager->admin_id,
            ]);
        } catch (\RuntimeException $exception) {
            return $this->confirmPrompt($draft, $manager, $exception->getMessage());
        } catch (\Throwable $exception) {
            Log::error('telegram product shot save failed', ['draft' => $draft->id, 'error' => $exception->getMessage()]);

            return $this->confirmPrompt($draft, $manager, 'ثبت پک با خطا روبه‌رو شد؛ چند لحظه بعد دوباره امتحان کن.');
        }

        $draft->forceFill([
            'product_id' => $product->id,
            'state' => $status === 'active' ? 'published' : 'draft_saved',
            'completed_at' => now(),
        ])->save();
        if (Schema::hasTable('telegram_product_registrations')) {
            TelegramProductRegistration::query()->updateOrCreate(['product_id' => $product->id], [
                'telegram_product_manager_id' => $draft->telegram_product_manager_id,
                'draft_id' => $draft->id,
                'telegram_id' => $draft->telegram_id,
                'input_prompt' => $draft->description,
                'ai_result' => array_merge($ai, ['product_mode' => 'product', 'shots' => $shots]),
                'status' => $status,
            ]);
        }

        $buttons = [];
        if ($status === 'active' && $product->short_code) {
            $buttons[] = [['text' => '🌐 مشاهده در سایت', 'url' => route('app.pro', ['product' => $product->short_code])]];
        }
        $buttons[] = [['text' => '🛠 ویرایش کامل در داشبورد', 'url' => route('admin.product-shots.products.create', ['product' => $product->id])]];
        $buttons[] = [['text' => '➕ ثبت محصول بعدی', 'callback_data' => 'product:start']];
        $text = ($status === 'active' ? "✅ <b>پک با موفقیت ثبت و منتشر شد.</b>" : "✅ <b>پک در پیش‌نویس ذخیره شد.</b>")
            . "\n\nکد محصول: <code>{$product->product_code}</code>"
            . "\nنام: " . $this->e($product->name_fa)
            . "\nشات‌ها: " . $this->fa(count($order)) . ' (بسته‌ی آماده: ' . $this->fa(count($defaults)) . ')'
            . "\n\nبرای افزودن نمونه‌عکس هر شات، تغییر قیمت یا مدل، «ویرایش کامل در داشبورد» را بزن.";

        return $this->response($draft->chat_id, $text, $buttons, [
            'status' => $product->status,
            'draft_id' => $draft->id,
            'product_id' => $product->id,
            'final_product' => true,
            'photo_url' => $this->coverUrl($draft),
        ]);
    }

    // ───────────────────────── مسیریابی ─────────────────────────

    private function callback(TelegramProductManager $manager, TelegramProductDraft $draft, string $chatId, string $callback, array $input): array
    {
        $parts = explode(':', $callback, 3);
        $section = $parts[1] ?? '';
        $value = $parts[2] ?? '';

        return match (true) {
            $section === 'status' => $this->resultResponse($draft),
            $section === 'retry' => $draft->state === 'failed' && trim((string) $draft->description) !== ''
                ? $this->beginProcessing($draft, (string) $draft->description)
                : $this->resultResponse($draft),
            $section === 'img' && $value === 'done' => $draft->state === 'ps_images' && ((array) ($this->data($draft)['images'] ?? [])) !== []
                ? $this->groupPrompt($draft)
                : $this->imagesPrompt($draft, 'اول حداقل یک عکس بفرست.'),
            $section === 'img' && $value === 'reset' => $this->resetImages($draft),
            $section === 'g' && $value === 'done' => $this->selectedOccupations($draft)->isEmpty()
                ? $this->groupPrompt($draft)
                : ($draft->ai_result ? $this->review($draft) : $this->briefPrompt($draft)),
            $section === 'g' => $this->occupationsPrompt($draft, $value),
            $section === 'o' => $draft->state === 'ps_occupations' ? $this->toggleOccupation($draft, $value) : $this->current($draft, null),
            $section === 'rv' && $value === 'ok' => $draft->state === 'ps_review' ? $this->shotsPrompt($draft) : $this->current($draft, null),
            $section === 'rv' && $value === 'back' => $draft->ai_result ? $this->review($draft) : $this->current($draft, null),
            $section === 'ed' => $draft->ai_result ? $this->editPrompt($draft, $value) : $this->current($draft, null),
            $section === 's' => $draft->state === 'ps_shots' ? $this->shotsAction($draft, $value) : $this->current($draft, null),
            $section === 'c' && $value === 'done' => $draft->state === 'ps_categories' ? $this->confirmPrompt($draft, $manager) : $this->current($draft, null),
            $section === 'c' => $draft->state === 'ps_categories' ? $this->toggleCategory($draft, (int) $value) : $this->current($draft, null),
            $section === 'cp' => $draft->state === 'ps_categories' ? $this->categoriesPrompt($draft, (int) $value) : $this->current($draft, null),
            $section === 'save' && $value === 'active' => $draft->state === 'ps_confirm' ? $this->tilesPrompt($draft) : $this->current($draft, null),
            $section === 'save' => $this->save($draft, $manager, 'draft'),
            $section === 't' && $value === 'done' => $draft->state === 'ps_tiles' ? $this->save($draft, $manager, 'active') : $this->current($draft, null),
            $section === 't' => $draft->state === 'ps_tiles' ? $this->toggleTile($draft, $value) : $this->current($draft, null),
            $section === 'back' => $this->back($draft, $manager, $value),
            default => $this->current($draft, 'این دکمه دیگر معتبر نیست.'),
        };
    }

    private function back(TelegramProductDraft $draft, TelegramProductManager $manager, string $target): array
    {
        if ($draft->state === 'processing') {
            return $this->resultResponse($draft);
        }

        return match ($target) {
            'images' => $this->toImages($draft),
            'group' => $this->groupPrompt($draft),
            'brief' => $this->briefPrompt($draft),
            'shots' => $draft->ai_result ? $this->shotsPrompt($draft) : $this->current($draft, null),
            'categories' => $draft->ai_result ? $this->categoriesPrompt($draft, (int) data_get($draft->input_payload, 'pshot.category_page', 0)) : $this->current($draft, null),
            'confirm' => $draft->ai_result ? $this->confirmPrompt($draft, $manager) : $this->current($draft, null),
            default => $this->current($draft, null),
        };
    }

    private function toImages(TelegramProductDraft $draft): array
    {
        $draft->forceFill(['state' => 'ps_images'])->save();

        return $this->imagesPrompt($draft, null);
    }

    private function resetImages(TelegramProductDraft $draft): array
    {
        $payload = (array) $draft->input_payload;
        $paths = (array) data_get($payload, 'pshot.images', []);
        data_set($payload, 'pshot.images', []);
        $draft->forceFill(['input_payload' => $payload, 'image_paths' => [], 'state' => 'ps_images'])->save();
        foreach ($paths as $path) {
            if (is_string($path) && Str::startsWith($path, 'products/main/')) {
                Storage::disk('public')->delete($path);
            }
        }

        return $this->imagesPrompt($draft->fresh(), 'عکس‌ها حذف شدند؛ عکس‌های جدید را بفرست.');
    }

    private function text(TelegramProductDraft $draft, string $chatId, string $text): array
    {
        return match ($draft->state) {
            'ps_brief' => $this->receiveBrief($draft, $text),
            'ps_edit' => $this->applyEdit($draft, $text),
            'failed' => mb_strlen($text) >= 15 ? $this->beginProcessing($draft, Str::limit($text, 2000, '')) : $this->failed($draft),
            'ps_images' => $this->imagesPrompt($draft, 'در این مرحله فقط عکس بفرست؛ بعد از ارسال عکس‌ها «ادامه» را بزن.'),
            default => $this->current($draft, 'لطفاً از دکمه‌های پیام استفاده کن.'),
        };
    }

    /** نمایش دوباره‌ی همان مرحله‌ای که درفت در آن است. */
    private function current(TelegramProductDraft $draft, ?string $notice): array
    {
        $response = match ($draft->state) {
            'ps_images' => $this->imagesPrompt($draft, $notice),
            'ps_group' => $this->groupPrompt($draft),
            'ps_occupations' => $this->occupationsPrompt($draft, (string) data_get($draft->input_payload, 'pshot.group', '')),
            'ps_brief' => $this->briefPrompt($draft, $notice),
            'processing', 'failed' => $this->resultResponse($draft),
            'ps_review' => $this->review($draft),
            'ps_edit' => $this->editPrompt($draft, (string) $draft->pending_edit_field),
            'ps_shots' => $this->shotsPrompt($draft, $notice),
            'ps_categories' => $this->categoriesPrompt($draft, (int) data_get($draft->input_payload, 'pshot.category_page', 0), $notice),
            'ps_confirm' => $this->confirmPrompt($draft, $draft->manager ?? new TelegramProductManager(), $notice),
            'ps_tiles' => $this->tilesPrompt($draft, $notice),
            default => $this->response($draft->chat_id, 'این فرآیند تمام شده است. برای ثبت جدید «ثبت محصول» را بزن.', [], ['status' => (string) $draft->state, 'draft_id' => $draft->id]),
        };
        if ($notice) {
            $response['callback_notice'] = $notice;
        }

        return $response;
    }

    private function cancel(TelegramProductDraft $draft, string $chatId): array
    {
        $draft->forceFill(['state' => 'cancelled', 'cancelled_at' => now(), 'pending_edit_field' => null])->save();

        return $this->response($chatId, 'فرآیند ثبت پک لغو شد؛ هیچ محصولی ثبت یا حذف نشد.', [], [
            'status' => 'cancelled',
            'draft_id' => $draft->id,
            'reply_markup' => [
                'keyboard' => [[['text' => 'ثبت محصول'], ['text' => 'آموزش']]],
                'resize_keyboard' => true,
                'is_persistent' => true,
            ],
        ]);
    }

    // ───────────────────────── کمکی‌ها ─────────────────────────

    private function data(TelegramProductDraft $draft): array
    {
        return (array) data_get($draft->input_payload, 'pshot', []);
    }

    private function selectedOccupations(TelegramProductDraft $draft): Collection
    {
        $ids = array_map('intval', (array) ($this->data($draft)['occupation_ids'] ?? []));

        return $ids === [] ? collect() : Occupation::query()->whereIn('id', $ids)->ordered()->get(['id', 'name_fa', 'group_key']);
    }

    private function selectedCategories(TelegramProductDraft $draft): Collection
    {
        $ids = array_map('intval', (array) ($this->data($draft)['category_ids'] ?? []));

        return $ids === [] ? collect() : Category::query()->whereIn('id', $ids)->get(['id', 'name', 'name_fa']);
    }

    private function availableShots(TelegramProductDraft $draft): Collection
    {
        $groups = $this->registrar->groupsOfOccupations($this->selectedOccupations($draft)->pluck('id')->all());

        return $this->registrar->shotsForGroups($groups);
    }

    private function selectedTiles(TelegramProductDraft $draft): array
    {
        $tiles = array_values(array_intersect(array_keys(self::TILE_LABELS), (array) ($this->data($draft)['tiles'] ?? [])));

        return $tiles ?: ['1x1'];
    }

    private function coverUrl(TelegramProductDraft $draft): ?string
    {
        $path = collect((array) ($this->data($draft)['images'] ?? []))->first();

        return $path ? Storage::disk('public')->url((string) $path) : null;
    }

    private function matchCategory(string $value, Collection $categories): ?Category
    {
        $needle = $this->normalize($value);
        if ($needle === '') {
            return null;
        }

        return $categories->first(fn (Category $c) => in_array($needle, [$this->normalize((string) $c->name_fa), $this->normalize((string) $c->name)], true))
            ?? $categories->first(fn (Category $c) => ($n = $this->normalize((string) ($c->name_fa ?: $c->name))) !== '' && (str_contains($n, $needle) || str_contains($needle, $n)));
    }

    private function duplicateProduct(string $nameFa, string $nameEn): ?Product
    {
        $fa = $this->normalize($nameFa);
        $en = $this->normalize($nameEn);
        if ($fa === '' && $en === '') {
            return null;
        }

        return Product::query()->whereIn('status', ['draft', 'active', 'inactive'])->get(['id', 'name_fa', 'name_en', 'product_code'])
            ->first(fn (Product $p) => ($fa !== '' && $fa === $this->normalize((string) $p->name_fa)) || ($en !== '' && $en === $this->normalize((string) $p->name_en)));
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace(['ي', 'ى', 'ك', 'ۀ', 'ة', '‌'], ['ی', 'ی', 'ک', 'ه', 'ه', ' '], mb_strtolower($value))) ?: '');
    }

    private function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function fa(int $number): string
    {
        return strtr((string) $number, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }

    /** پاسخ استاندارد بات با کیبورد شیشه‌ای (ردیف‌ها از پیش چیده شده‌اند). */
    private function response(string $chatId, string $text, array $rows = [], array $extra = []): array
    {
        $flat = [];
        foreach ($rows as $row) {
            foreach ($row as $button) {
                $flat[] = $button;
            }
        }
        $response = [
            'ok' => true,
            'action' => 'send_message',
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'buttons' => $flat,
            'flow' => 'product_shot',
        ];
        if ($rows !== []) {
            $response['reply_markup'] = ['inline_keyboard' => array_values($rows)];
        }

        return array_merge($response, $extra);
    }
}
