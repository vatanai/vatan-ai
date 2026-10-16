<?php

namespace App\Services\TelegramProduct;

use App\Jobs\ProcessTelegramProductDraftJob;
use App\Models\Category;
use App\Models\Occupation;
use App\Models\Product;
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
 * مسیر «پروداکتی» بات ثبت محصول — درست مثل بات پرتره، ولی با اسلاید.
 *
 * گام‌ها: عکس ورودی (یک عکس) ← اسلایدها (هر اسلاید = عکس نمونه + پرامپت ساخت، پرامپت بلند
 * در چند پیام) ← آماده‌سازی هوشمند در صف (اصلاح پرامپت با «پرامپت اصلاح‌گر» داشبورد، نام/توضیح/
 * هشتگ/صنف/دسته/نام اسلایدها با «پرامپت مادر» داشبورد) ← بازبینی و اصلاح ← پیش‌نویس یا انتشار.
 *
 * پاک‌سازی: پیام‌های مدیر بلافاصله بعد از دریافت پاک می‌شوند، بات فقط یک «پنل» زنده دارد و
 * بعد از ثبت، تنها پیام ثبت نهایی در چت می‌ماند. فقط در اتصال مستقیم (بدون n8n) فعال است.
 * همه‌ی دکمه‌ها شیشه‌ای‌اند و callback آن‌ها با «ps:» شروع می‌شود.
 */
class ProductShotBotFlow
{
    public const STATES = [
        'ps_input', 'ps_slides', 'ps_review', 'ps_edit', 'ps_group', 'ps_occupations',
        'ps_categories', 'ps_slide_list', 'ps_tiles',
    ];

    /** وضعیت‌های نسخه‌ی قبلی مسیر (پیش از ۲۴ مهر)؛ درفت‌های باز آن‌ها باید از نو شروع شوند. */
    public const LEGACY_STATES = ['ps_images', 'ps_brief', 'ps_shots', 'ps_confirm'];

    private const CATEGORY_PAGE = 16;
    private const GLOBAL_TEXTS = ['ثبت محصول', 'ثبت محصول جدید', 'ویرایش محصول', 'تنظیمات', 'آموزش', 'لغو فرآیند'];
    private const EDIT_FIELDS = ['name_fa' => 'نام فارسی', 'name_en' => 'نام انگلیسی', 'description_fa' => 'توضیح فارسی'];
    private const INPUT_DIR = 'products/input';
    private const SAMPLE_DIR = 'products/shot-samples';
    /** عکس با ضلع بلند کمتر از این، برای نمونه‌ی اسلاید کم‌کیفیت است. */
    private const LOW_RES_EDGE = 1000;

    /** گزارش بهینه‌سازی آخرین عکس دریافتی (فقط برای همان پاسخ). */
    private ?string $imageInfo = null;

    public function __construct(
        private readonly ShotProductRegistrar $registrar,
        private readonly ProductImageOptimizer $images,
        private readonly OpenRouterService $openRouter,
        private readonly ProductShotBotSettings $settings,
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
        return $this->response($chatId, $this->settings->text('type_choice'), [
            [['text' => '🧑 پرتره', 'callback_data' => 'ps:type:portrait'], ['text' => '📦 پروداکتی', 'callback_data' => 'ps:type:product']],
            [['text' => 'لغو', 'callback_data' => 'ps:x']],
        ], ['status' => 'type_choice', 'consume_input' => true]);
    }

    public function start(TelegramProductManager $manager, string $chatId): array
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
            'state' => 'ps_input',
            'product_mode' => TelegramProductDraft::MODE_PRODUCT,
            'input_payload' => ['transport' => 'direct', 'pshot' => ['input_image' => null, 'slides' => []]],
        ]);

        return $this->inputPrompt($draft, null);
    }

    public function handle(TelegramProductManager $manager, ?TelegramProductDraft $draft, string $chatId, array $input, string $eventType, ?UploadedFile $image): ?array
    {
        $callback = (string) ($input['callback_data'] ?? '');
        if ($callback === 'ps:x') {
            return $draft && $draft->isProductMode() ? $this->cancel($draft, $chatId) : $this->response($chatId, 'باشه، چیزی ثبت نشد.', [], ['status' => 'cancelled']);
        }
        if ($callback === 'ps:type:product') {
            return $this->start($manager, $chatId);
        }
        if (! $draft || ! $draft->isProductMode()) {
            return $this->response($chatId, 'این دکمه دیگر معتبر نیست. برای شروع «ثبت محصول» را بزنید.', [], ['status' => 'no_draft', 'callback_notice' => 'فرآیند فعالی نیست']);
        }
        if (in_array($draft->state, self::LEGACY_STATES, true)) {
            $draft->forceFill(['state' => 'cancelled', 'cancelled_at' => now()])->save();

            return $this->response($chatId, 'مسیر ثبت پروداکتی به‌روز شده است؛ لطفاً «ثبت محصول» را بزن و از نو شروع کن.', [
                [['text' => '➕ ثبت محصول', 'callback_data' => 'product:start']],
            ], ['status' => 'cancelled', 'consume_input' => $eventType !== 'callback']);
        }

        $response = match ($eventType) {
            'photo' => $this->photo($draft, $image, $input),
            'callback' => $this->callback($manager, $draft, $chatId, $callback),
            default => $this->text($draft, trim((string) ($input['text'] ?? '')), $input),
        };
        // پیام‌های مدیر (عکس و متن) بعد از دریافت پاک می‌شوند تا چت فقط پنل بات را داشته باشد.
        if ($eventType !== 'callback' && ! array_key_exists('consume_input', $response)) {
            $response['consume_input'] = true;
        }

        return $response;
    }

    // ───────────────────────── گام ۱: عکس ورودی ─────────────────────────

    private function inputPrompt(TelegramProductDraft $draft, ?string $notice): array
    {
        if ($draft->state !== 'ps_input') {
            $draft->forceFill(['state' => 'ps_input'])->save();
        }
        $text = $this->settings->text('input_image') . $this->takeImageInfo();
        if ($notice) {
            $text .= "\n\n⚠️ " . $this->e($notice);
        }

        return $this->response($draft->chat_id, $text, [
            [['text' => 'لغو فرآیند', 'callback_data' => 'ps:x']],
        ], ['status' => 'ps_input', 'draft_id' => $draft->id]);
    }

    private function photo(TelegramProductDraft $draft, ?UploadedFile $image, array $input): array
    {
        if (! in_array($draft->state, ['ps_input', 'ps_slides'], true)) {
            return $this->current($draft, 'الان منتظر عکس نیستم؛ از دکمه‌های همین پیام استفاده کن.');
        }
        if (! $image) {
            return $this->current($draft, (string) ($input['file_error'] ?? '') ?: 'فایل عکس دریافت نشد؛ دوباره بفرست.');
        }
        $isInput = $draft->state === 'ps_input';
        $originalBytes = (int) (@filesize($image->getRealPath()) ?: 0);
        $originalSize = @getimagesize($image->getRealPath()) ?: [0, 0];
        try {
            // دقیقاً همان بهینه‌ساز ثبت از داشبورد (ProductImageOptimizer):
            // چرخش خودکار، sRGB، حداکثر ضلع ۱۶۰۰ پیکسل، WebP با کیفیت ۸۸ و حذف متادیتا.
            $path = $this->images->store($image, $isInput ? self::INPUT_DIR : self::SAMPLE_DIR);
        } catch (\Throwable $exception) {
            Log::warning('telegram product shot image failed', ['draft' => $draft->id, 'error' => $exception->getMessage()]);

            return $this->current($draft, 'این فایل قابل خواندن نیست؛ یک عکس JPG، PNG یا WebP دیگر بفرست.');
        }
        $this->imageInfo = $this->optimizationReport($path, $originalBytes, (int) ($originalSize[0] ?? 0), (int) ($originalSize[1] ?? 0), $isInput);
        $caption = trim((string) ($input['caption'] ?? ''));
        $messageId = (int) ($input['message_id'] ?? 0);

        $result = DB::transaction(function () use ($draft, $path, $isInput, $caption, $messageId): array {
            $locked = TelegramProductDraft::query()->whereKey($draft->id)->lockForUpdate()->firstOrFail();
            $payload = (array) $locked->input_payload;
            $data = (array) ($payload['pshot'] ?? []);
            $notice = null;
            $remove = [];

            if ($isInput && $locked->state === 'ps_input') {
                if (! empty($data['input_image'])) {
                    $remove[] = $data['input_image'];
                }
                $data['input_image'] = $path;
                $locked->state = 'ps_slides';
                $data['slides'] = array_values((array) ($data['slides'] ?? []));
                if ($data['slides'] === []) {
                    $data['slides'][] = $this->newSlide();
                }
            } else {
                $slides = array_values((array) ($data['slides'] ?? []));
                $index = $this->currentIndex($slides);
                $currentSlide = $index !== null ? $slides[$index] : null;
                if ($currentSlide && ! empty($currentSlide['image']) && $this->parts($currentSlide) !== []) {
                    // اسلاید فعلی کامل است؛ این عکس یعنی شروع اسلاید بعدی.
                    if (count($slides) >= $this->settings->maxSlides()) {
                        $remove[] = $path;

                        return ['draft' => $locked, 'remove' => $remove, 'notice' => 'سقف ' . $this->fa($this->settings->maxSlides()) . ' اسلاید پر شده است؛ «پایان و آماده‌سازی» را بزن.'];
                    }
                    $slides[$index]['done'] = true;
                    $slides[] = $this->newSlide();
                    $index = count($slides) - 1;
                } elseif ($currentSlide === null) {
                    if (count($slides) >= $this->settings->maxSlides()) {
                        $remove[] = $path;

                        return ['draft' => $locked, 'remove' => $remove, 'notice' => 'سقف اسلایدها پر شده است.'];
                    }
                    $slides[] = $this->newSlide();
                    $index = count($slides) - 1;
                } elseif (! empty($currentSlide['image'])) {
                    $remove[] = $currentSlide['image'];
                    $notice = 'هر اسلاید یک عکس نمونه دارد؛ عکس قبلی این اسلاید جایگزین شد.';
                }
                $slides[$index]['image'] = $path;
                if ($caption !== '') {
                    $slides[$index]['parts'][] = ['m' => $messageId, 't' => $caption];
                }
                $data['slides'] = $slides;
            }

            $payload['pshot'] = $data;
            $locked->forceFill(['input_payload' => $payload])->save();

            return ['draft' => $locked, 'remove' => $remove, 'notice' => $notice];
        });

        $this->deleteFiles($result['remove']);

        return $result['draft']->state === 'ps_input'
            ? $this->inputPrompt($result['draft'], $result['notice'])
            : $this->slidesPanel($result['draft'], $result['notice']);
    }

    /** گزارش کوتاه بهینه‌سازی برای مدیر: حجم/ابعاد قبل و بعد + هشدار کیفیت پایین. */
    private function optimizationReport(string $path, int $originalBytes, int $originalWidth, int $originalHeight, bool $isInput): ?string
    {
        try {
            $disk = Storage::disk('public');
            $newBytes = (int) $disk->size($path);
            $size = @getimagesize($disk->path($path)) ?: [0, 0];
        } catch (\Throwable) {
            return null;
        }
        $width = (int) ($size[0] ?? 0);
        $height = (int) ($size[1] ?? 0);
        $label = $isInput ? 'عکس ورودی' : 'عکس اسلاید';
        $line = "🗜 {$label} بهینه شد: "
            . ($originalBytes > 0 ? $this->humanBytes($originalBytes) . ' ← ' : '')
            . $this->humanBytes($newBytes)
            . ($width > 0 ? ' · ' . $this->fa($width) . '×' . $this->fa($height) : '')
            . ' · WebP';
        $longest = max($originalWidth, $originalHeight);
        if ($longest > 0 && $longest < self::LOW_RES_EDGE) {
            $line .= "\n⚠️ کیفیت این عکس پایین است (" . $this->fa($longest) . ' پیکسل)؛ برای کیفیت بهتر، عکس را «به‌صورت فایل» بفرست.';
        }

        return "\n\n" . $line;
    }

    private function takeImageInfo(): string
    {
        $info = (string) $this->imageInfo;
        $this->imageInfo = null;

        return $info;
    }

    private function humanBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return $this->fa(number_format($bytes / 1048576, 1)) . ' مگابایت';
        }

        return $this->fa(max(1, (int) round($bytes / 1024))) . ' کیلوبایت';
    }

    // ───────────────────────── گام ۲: اسلایدها ─────────────────────────

    private function slidesPanel(TelegramProductDraft $draft, ?string $notice = null, bool $rejectedInput = false): array
    {
        if ($draft->state !== 'ps_slides') {
            $draft->forceFill(['state' => 'ps_slides'])->save();
        }
        $slides = $this->slides($draft);
        $index = $this->currentIndex($slides);
        $complete = collect($slides)->filter(fn ($s) => $this->isComplete($s))->count();
        $rows = [];

        if ($index === null) {
            $text = $this->settings->text('slides_menu', ['n' => $this->fa(count($slides)), 'count' => $this->fa($complete)]);
            $rows[] = [['text' => '➕ اسلاید بعدی', 'callback_data' => 'ps:sl:new']];
        } else {
            $slide = $slides[$index];
            $n = $this->fa($index + 1);
            $parts = $this->parts($slide);
            $chars = mb_strlen($this->joinParts($slide));
            if (empty($slide['image'])) {
                $text = $this->settings->text('slide_image', ['n' => $n]);
            } elseif ($parts === []) {
                $text = $this->settings->text('slide_prompt', ['n' => $n]);
            } else {
                $text = "🖼 <b>اسلاید {$n}</b>\n\n"
                    . "📝 پرامپت: " . $this->fa(count($parts)) . ' بخش · ' . $this->fa(number_format($chars)) . " کاراکتر دریافت شد ✅\n"
                    . "اگر پرامپت ادامه دارد، بقیه را بفرست؛ همه پشت سر هم به همین اسلاید اضافه می‌شود.";
            }
            $text .= "\n\n<b>وضعیت اسلاید {$n}:</b> " . (empty($slide['image']) ? '⏳ عکس' : '✅ عکس') . ' · ' . ($parts === [] ? '⏳ پرامپت' : '✅ پرامپت');
            $done = $complete - ($this->isComplete($slide) ? 1 : 0);
            if ($done > 0) {
                $text .= "\nاسلایدهای ثبت‌شده‌ی قبلی: " . $this->fa($done);
            }
            if ($this->isComplete($slide)) {
                if (count($slides) < $this->settings->maxSlides()) {
                    $rows[] = [['text' => '➕ ثبت این اسلاید و اسلاید بعدی', 'callback_data' => 'ps:sl:next']];
                }
                $rows[] = [['text' => '🏁 پایان اسلایدها و آماده‌سازی', 'callback_data' => 'ps:sl:finish']];
                $rows[] = [['text' => '🧹 پاک کردن پرامپت این اسلاید', 'callback_data' => 'ps:sl:clear']];
            } else {
                if ($parts !== []) {
                    $rows[] = [['text' => '🧹 پاک کردن پرامپت این اسلاید', 'callback_data' => 'ps:sl:clear']];
                }
                if (count($slides) > 1 || $complete > 0) {
                    $rows[] = [['text' => '🗑 حذف این اسلاید ناقص', 'callback_data' => 'ps:sl:drop']];
                }
            }
        }
        if ($index === null && $complete > 0) {
            $rows[] = [['text' => '🏁 پایان اسلایدها و آماده‌سازی', 'callback_data' => 'ps:sl:finish']];
        }
        $text .= $this->takeImageInfo();
        $text .= "\n\n<i>حداکثر " . $this->fa($this->settings->maxSlides()) . ' اسلاید · هر پرامپت تا ' . $this->fa(number_format($this->settings->maxPromptChars())) . ' کاراکتر</i>';
        if ($notice) {
            $text .= "\n\n⚠️ " . $this->e($notice);
        }
        if ($draft->ai_result) {
            $rows[] = [['text' => '⬅️ برگشت به بازبینی', 'callback_data' => 'ps:rv:back']];
        }
        $rows[] = [['text' => 'لغو فرآیند', 'callback_data' => 'ps:x']];

        $extra = ['status' => 'ps_slides', 'draft_id' => $draft->id];
        if ($rejectedInput) {
            $extra['consume_input'] = false;
        }

        return $this->response($draft->chat_id, $text, $rows, $extra);
    }

    private function receivePromptPart(TelegramProductDraft $draft, string $text, array $input): array
    {
        $messageId = (int) ($input['message_id'] ?? 0);
        $max = $this->settings->maxPromptChars();

        $result = DB::transaction(function () use ($draft, $text, $messageId, $max): array {
            $locked = TelegramProductDraft::query()->whereKey($draft->id)->lockForUpdate()->firstOrFail();
            $payload = (array) $locked->input_payload;
            $slides = array_values((array) data_get($payload, 'pshot.slides', []));
            $index = $this->currentIndex($slides);
            if ($index === null) {
                if (count($slides) >= $this->settings->maxSlides()) {
                    return ['draft' => $locked, 'notice' => 'سقف اسلایدها پر شده است؛ «پایان اسلایدها» را بزن.', 'rejected' => true];
                }
                $slides[] = $this->newSlide();
                $index = count($slides) - 1;
            }
            $current = mb_strlen($this->joinParts($slides[$index]));
            if ($current + mb_strlen($text) > $max) {
                return ['draft' => $locked, 'notice' => 'پرامپت این اسلاید از سقف ' . $this->fa(number_format($max)) . ' کاراکتر بیشتر می‌شود؛ این بخش اضافه نشد (پیامت پاک نشد تا کوتاهش کنی).', 'rejected' => true];
            }
            $slides[$index]['parts'][] = ['m' => $messageId, 't' => $text];
            data_set($payload, 'pshot.slides', $slides);
            $locked->forceFill(['input_payload' => $payload])->save();

            return ['draft' => $locked, 'notice' => null, 'rejected' => false];
        });

        return $this->slidesPanel($result['draft'], $result['notice'], $result['rejected']);
    }

    private function slideAction(TelegramProductDraft $draft, string $action): array
    {
        if (! in_array($draft->state, ['ps_slides', 'ps_slide_list', 'ps_review'], true)) {
            return $this->current($draft, null);
        }
        $payload = (array) $draft->input_payload;
        $slides = array_values((array) data_get($payload, 'pshot.slides', []));
        $index = $this->currentIndex($slides);
        $notice = null;
        $remove = [];

        switch ($action) {
            case 'next':
                if ($index === null || ! $this->isComplete($slides[$index])) {
                    return $this->slidesPanel($draft, 'اول عکس و پرامپت همین اسلاید را کامل کن.');
                }
                if (count($slides) >= $this->settings->maxSlides()) {
                    return $this->slidesPanel($draft, 'سقف اسلایدها پر شده است.');
                }
                $slides[$index]['done'] = true;
                $slides[] = $this->newSlide();
                break;
            case 'new':
                if (count($slides) >= $this->settings->maxSlides()) {
                    return $this->slidesPanel($draft, 'سقف اسلایدها پر شده است.');
                }
                if ($index === null) {
                    $slides[] = $this->newSlide();
                }
                break;
            case 'clear':
                if ($index !== null) {
                    $slides[$index]['parts'] = [];
                    $notice = 'پرامپت این اسلاید پاک شد؛ پرامپت جدید را بفرست.';
                }
                break;
            case 'drop':
                if ($index !== null) {
                    if (! empty($slides[$index]['image'])) {
                        $remove[] = $slides[$index]['image'];
                    }
                    array_splice($slides, $index, 1);
                }
                break;
            case 'finish':
                if ($index !== null) {
                    if ($this->isComplete($slides[$index])) {
                        $slides[$index]['done'] = true;
                    } elseif (empty($slides[$index]['image']) && $this->parts($slides[$index]) === []) {
                        array_splice($slides, $index, 1); // اسلاید خالی
                    } else {
                        return $this->slidesPanel($draft, 'این اسلاید ناقص است؛ کاملش کن یا «حذف این اسلاید ناقص» را بزن.');
                    }
                }
                if (! collect($slides)->contains(fn ($s) => $this->isComplete($s))) {
                    data_set($payload, 'pshot.slides', $slides);
                    $draft->forceFill(['input_payload' => $payload])->save();

                    return $this->slidesPanel($draft->fresh(), 'حداقل یک اسلاید کامل (عکس + پرامپت) لازم است.');
                }
                data_set($payload, 'pshot.slides', $slides);
                $draft->forceFill(['input_payload' => $payload])->save();

                return $this->beginProcessing($draft->fresh());
        }

        data_set($payload, 'pshot.slides', array_values($slides));
        $draft->forceFill(['input_payload' => $payload, 'state' => 'ps_slides'])->save();
        $this->deleteFiles($remove);

        return $this->slidesPanel($draft->fresh(), $notice);
    }

    // ───────────────────────── آماده‌سازی هوشمند (در صف) ─────────────────────────

    private function beginProcessing(TelegramProductDraft $draft): array
    {
        $draft->forceFill([
            'state' => 'processing',
            'pending_edit_field' => null,
            'error_message' => null,
            'processing_started_at' => now(),
            'reviewed_at' => null,
            'description' => Str::limit($this->slidesDigest($draft), 60000, ''),
        ])->save();
        ProcessTelegramProductDraftJob::dispatch($draft->id)->afterCommit();

        return $this->processingResponse($draft);
    }

    private function processingResponse(TelegramProductDraft $draft, bool $editPanel = false): array
    {
        $slides = $this->slides($draft);
        $pending = collect($slides)->filter(fn ($s) => $this->isComplete($s) && empty($s['optimized']))->count();
        $total = collect($slides)->filter(fn ($s) => $this->isComplete($s))->count();
        $text = $this->settings->text('processing');
        if ($total > 0 && (bool) $this->settings->get('ai.optimizer_enabled', true)) {
            $text .= "\n\n✨ اصلاح پرامپت‌ها: " . $this->fa($total - $pending) . ' از ' . $this->fa($total);
        }

        return $this->response($draft->chat_id, $text, [
            [['text' => '🔄 بررسی وضعیت', 'callback_data' => 'ps:status'], ['text' => 'لغو فرآیند', 'callback_data' => 'ps:x']],
        ], ['status' => 'processing', 'draft_id' => $draft->id, 'edit_panel' => $editPanel]);
    }

    /**
     * یک گام از آماده‌سازی؛ هر بار یک پرامپت اصلاح می‌شود تا هیچ جابی از سقف زمانی صف رد نشود.
     *
     * @return bool true یعنی کار تمام شد (بازبینی یا خطا)، false یعنی گام بعدی لازم است.
     */
    public function process(TelegramProductDraft $draft): bool
    {
        $draft->refresh();
        if ($draft->state !== 'processing') {
            return true;
        }

        try {
            $payload = (array) $draft->input_payload;
            $slides = array_values((array) data_get($payload, 'pshot.slides', []));
            $slides = array_values(array_filter($slides, fn ($s) => $this->isComplete($s)));

            // ۱) اصلاح پرامپت‌ها — فقط بر اساس «پرامپت اصلاح‌گر» داشبورد، یکی‌یکی.
            foreach ($slides as $i => $slide) {
                if (! empty($slide['optimized'])) {
                    continue;
                }
                $original = $this->joinParts($slide);
                $slides[$i]['prompt'] = $original;
                $slides[$i]['optimized'] = true;
                $slides[$i]['optimizer_applied'] = false;
                if ((bool) $this->settings->get('ai.optimizer_enabled', true)) {
                    $optimized = $this->optimizePrompt($original, $draft->id);
                    if ($optimized !== null) {
                        $slides[$i]['prompt'] = $optimized;
                        $slides[$i]['optimizer_applied'] = true;
                    }
                }
                data_set($payload, 'pshot.slides', $slides);
                $draft->forceFill(['input_payload' => $payload])->save();

                return false; // گام بعدی (اسلاید بعدی یا اطلاعات محصول) در جاب تازه
            }

            // ۲) نام، توضیح، هشتگ، صنف، دسته و نام اسلایدها — با «پرامپت مادر» داشبورد.
            $ai = (array) ($draft->ai_result ?? []);
            $needsMetadata = $ai === [] || collect($slides)->contains(fn ($s) => trim((string) ($s['name'] ?? '')) === '');
            if ($needsMetadata) {
                $generated = $this->generateMetadata($draft, $slides);
                foreach ($slides as $i => $slide) {
                    if (trim((string) ($slide['name'] ?? '')) === '') {
                        $slides[$i]['name'] = $generated['slide_names'][$i] ?? ('اسلاید ' . $this->fa($i + 1));
                    }
                }
                if ($ai === []) {
                    $ai = $generated;
                    data_set($payload, 'pshot.occupation_ids', $generated['occupation_ids']);
                    data_set($payload, 'pshot.category_ids', $generated['category_ids']);
                }
                unset($ai['slide_names'], $ai['occupation_ids'], $ai['category_ids']);
            }
            $duplicate = $this->duplicateProduct((string) ($ai['name_fa'] ?? ''), (string) ($ai['name_en'] ?? ''));
            $ai['duplicate_product_name'] = $duplicate ? ($duplicate->name_fa ?: $duplicate->name_en) : null;
            $ai['duplicate_product_code'] = $duplicate?->product_code;
            $ai['optimized_count'] = collect($slides)->filter(fn ($s) => ! empty($s['optimizer_applied']))->count();

            data_set($payload, 'pshot.slides', $slides);
            $draft->forceFill([
                'ai_result' => $ai,
                'input_payload' => $payload,
                'state' => 'ps_review',
                'error_message' => null,
                'reviewed_at' => null,
            ])->save();
        } catch (\Throwable $exception) {
            Log::warning('telegram product shot processing failed', ['draft' => $draft->id, 'error' => $exception->getMessage()]);
            $draft->forceFill([
                'state' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 1000, ''),
            ])->save();
        }

        return true;
    }

    private function optimizePrompt(string $prompt, string $draftId): ?string
    {
        $system = trim((string) $this->settings->get('ai.optimizer_prompt', '')) ?: ProductShotBotSettings::defaultOptimizerPrompt();
        // فقط قالب خروجی؛ خود قواعد اصلاح همان پرامپت داشبورد است.
        $system .= "\n\nOutput format: return only a JSON object {\"prompt\": \"<the final prompt>\"}.";
        try {
            $result = $this->openRouter->generateStructuredText($system, $prompt, $this->aiModel(), 75);
            $optimized = trim((string) data_get($result, 'content.prompt', ''));
        } catch (\Throwable $exception) {
            Log::info('telegram product shot prompt optimizer skipped', ['draft' => $draftId, 'error' => $exception->getMessage()]);

            return null;
        }
        // پاسخ خالی یا ناقص هرگز جایگزین پرامپت مدیر نمی‌شود.
        if ($optimized === '' || mb_strlen($optimized) < min(40, (int) (mb_strlen($prompt) * 0.3))) {
            return null;
        }

        return Str::limit($optimized, 30000, '');
    }

    private function generateMetadata(TelegramProductDraft $draft, array $slides): array
    {
        $occupations = Occupation::query()->active()->ordered()->get(['id', 'name_fa', 'group_key']);
        $categories = Category::query()->active()->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'name_fa']);
        $occupationList = $occupations->map(fn (Occupation $o) => "{$o->id}: {$o->name_fa} (" . (Occupation::GROUPS[$o->group_key] ?? $o->group_key) . ')')->implode("\n");
        $categoryList = $categories->map(fn (Category $c) => $c->name_fa ?: $c->name)->filter()->implode('، ');
        $slideList = collect($slides)->values()->map(fn ($s, $i) => '--- اسلاید ' . ($i + 1) . " ---\n" . Str::limit((string) ($s['prompt'] ?? $this->joinParts($s)), 1800))->implode("\n\n");
        $count = count($slides);

        $instruction = trim((string) $this->settings->get('ai.metadata_prompt', '')) ?: ProductShotBotSettings::defaultMetadataPrompt();
        $instruction .= "\n\n=== داده‌ها ===\n"
            . "تعداد اسلایدها: {$count}\n\nپرامپت ساخت اسلایدها:\n{$slideList}\n\n"
            . "صنف‌های مجاز (شناسه: نام (گروه)):\n{$occupationList}\n\n"
            . "دسته‌بندی‌های مجاز سایت:\n{$categoryList}\n\n"
            . (((bool) $this->settings->get('ai.use_vision', true)) ? "تصویر اول (اگر هست) عکس ورودی محصول و بقیه نمونه‌ی خروجی اسلایدها هستند.\n\n" : '')
            // قرارداد ثابت خروجی (برای اینکه تغییر پرامپت در داشبورد ساختار را خراب نکند)
            . "=== خروجی ===\nفقط یک JSON معتبر با دقیقاً این کلیدها:\n"
            . "name_fa (string), name_en (string), description_fa (string), tags (array of string, بدون #),\n"
            . "slide_names (array of {$count} string فارسی، به ترتیب اسلایدها),\n"
            . "occupation_ids (array of int، ۱ تا ۸ شناسه از صنف‌های مجاز),\n"
            . "categories (array of string، ۱ تا ۳ نام دقیقاً از دسته‌بندی‌های مجاز),\n"
            . "product_description (string انگلیسی، حداکثر ۲۵۰ کاراکتر: نوع و شکل فیزیکی محصول بدون نام برند),\n"
            . "brand_style (string انگلیسی کوتاه), brand_palette (string انگلیسی کوتاه یا خالی).";

        $model = $this->aiModel();
        $result = null;
        $images = (bool) $this->settings->get('ai.use_vision', true) ? $this->visionImages($draft, $slides) : [];
        if ($images !== []) {
            try {
                $result = $this->openRouter->analyzeImagesJson($model, $instruction, $images, 75);
            } catch (\Throwable $exception) {
                Log::info('telegram product shot vision metadata fallback to text', ['draft' => $draft->id, 'error' => $exception->getMessage()]);
            }
        }
        $result ??= $this->openRouter->generateStructuredText('فقط JSON معتبر برگردان.', $instruction, $model, 75);
        $ai = (array) ($result['content'] ?? []);
        foreach (['name_fa', 'name_en', 'description_fa'] as $key) {
            if (trim((string) ($ai[$key] ?? '')) === '') {
                throw new \RuntimeException("فیلد {$key} در پاسخ هوش مصنوعی خالی است.");
            }
        }

        $validOccupations = $occupations->pluck('id')->map(fn ($id) => (int) $id)->all();
        $occupationIds = array_values(array_unique(array_intersect(array_map('intval', (array) ($ai['occupation_ids'] ?? [])), $validOccupations)));
        $categoryIds = collect((array) ($ai['categories'] ?? [($ai['category'] ?? '')]))
            ->map(fn ($name) => $this->matchCategory((string) $name, $categories)?->id)
            ->filter()->unique()->take(3)->map(fn ($id) => (int) $id)->values()->all();
        $names = array_values(array_map(fn ($n) => Str::limit(trim((string) $n), 120, ''), (array) ($ai['slide_names'] ?? [])));

        return [
            'name_fa' => Str::limit(trim((string) $ai['name_fa']), 120, ''),
            'name_en' => Str::limit(trim((string) $ai['name_en']), 120, ''),
            'description_fa' => Str::limit(trim((string) $ai['description_fa']), 1500, ''),
            'tags' => collect((array) ($ai['tags'] ?? []))->map(fn ($t) => trim(ltrim((string) $t, '#')))->filter()->unique()->take(8)->values()->all(),
            'product_description' => Str::limit(trim((string) ($ai['product_description'] ?? '')), 300, ''),
            'brand_style' => Str::limit(trim((string) ($ai['brand_style'] ?? '')), 200, ''),
            'brand_palette' => Str::limit(trim((string) ($ai['brand_palette'] ?? '')), 120, ''),
            'slide_names' => array_map(fn ($n) => $n !== '' ? $n : null, $names),
            'occupation_ids' => $occupationIds,
            'category_ids' => $categoryIds,
            'model' => (string) ($result['model'] ?? $model),
        ];
    }

    private function visionImages(TelegramProductDraft $draft, array $slides): array
    {
        $paths = array_values(array_filter(array_merge(
            [data_get($draft->input_payload, 'pshot.input_image')],
            collect($slides)->pluck('image')->take(3)->all(),
        )));
        $images = [];
        foreach ($paths as $path) {
            if (! is_string($path) || ! Storage::disk('public')->exists($path)) {
                continue;
            }
            $bytes = Storage::disk('public')->get($path);
            if (! $bytes || strlen($bytes) > 4 * 1024 * 1024) {
                continue;
            }
            $mime = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';
            $images[] = 'data:' . $mime . ';base64,' . base64_encode($bytes);
        }

        return $images;
    }

    /** پیام بعد از اتمام پردازش (برای ارسال خودکار یا دکمه‌ی بررسی وضعیت). */
    public function resultResponse(TelegramProductDraft $draft, bool $progress = false): array
    {
        $draft->refresh();

        return match ($draft->state) {
            'processing' => $progress
                ? $this->processingResponse($draft, true)
                : array_merge($this->processingResponse($draft), ['callback_notice' => 'هنوز آماده نیست']),
            'failed' => $this->failed($draft),
            'ps_review' => $this->review($draft),
            default => $this->current($draft, null),
        };
    }

    private function failed(TelegramProductDraft $draft): array
    {
        return $this->response($draft->chat_id, "⚠️ آماده‌سازی هوشمند با خطا روبه‌رو شد.\n\nعکس‌ها و پرامپت‌ها حفظ شده‌اند. «تلاش مجدد» را بزن؛ اگر تکرار شد چند دقیقه بعد امتحان کن.", [
            [['text' => '🔁 تلاش مجدد', 'callback_data' => 'ps:retry']],
            [['text' => '🖼 ویرایش اسلایدها', 'callback_data' => 'ps:back:slides'], ['text' => 'لغو فرآیند', 'callback_data' => 'ps:x']],
        ], ['status' => 'failed', 'draft_id' => $draft->id]);
    }

    // ───────────────────────── گام ۳: بازبینی ─────────────────────────

    private function review(TelegramProductDraft $draft, ?string $notice = null): array
    {
        if ($draft->state !== 'ps_review') {
            $draft->forceFill(['state' => 'ps_review', 'pending_edit_field' => null])->save();
        }
        $ai = (array) $draft->ai_result;
        $slides = $this->completeSlides($draft);
        $tags = collect((array) ($ai['tags'] ?? []))->map(fn ($t) => '#' . str_replace([' ', '‌'], '_', $t))->implode(' ');
        $occupations = $this->selectedOccupations($draft)->pluck('name_fa')->implode('، ');
        $categories = $this->selectedCategories($draft)->map(fn ($c) => $c->name_fa ?: $c->name)->implode('، ');
        $credits = $this->settings->slideCredits();

        $text = $this->settings->text('review') . "\n\n"
            . '<b>' . $this->e($ai['name_fa'] ?? '') . '</b> — ' . $this->e($ai['name_en'] ?? '') . "\n"
            . $this->e(Str::limit((string) ($ai['description_fa'] ?? ''), 600)) . "\n\n"
            . '🏷 صنف: ' . $this->e($occupations ?: '⚠️ انتخاب نشده') . "\n"
            . '🗂 دسته: ' . $this->e($categories ?: '⚠️ انتخاب نشده') . "\n"
            . '#️⃣ ' . $this->e($tags ?: '—') . "\n\n"
            . '🖼 <b>اسلایدها (' . $this->fa(count($slides)) . "):</b>\n";
        foreach ($slides as $i => $slide) {
            $text .= $this->fa($i + 1) . '. ' . $this->e(Str::limit((string) ($slide['name'] ?? ''), 40)) . ' · ' . $this->fa(number_format(mb_strlen((string) ($slide['prompt'] ?? '')))) . " کاراکتر\n";
        }
        $text .= '📷 عکس ورودی: ' . (data_get($draft->input_payload, 'pshot.input_image') ? '✅' : '—')
            . ' · 💳 هر اسلاید از ' . $this->fa((int) ($credits['standard'] ?? 0)) . ' کردیت';
        $optimized = (int) ($ai['optimized_count'] ?? 0);
        if ($optimized > 0) {
            $text .= "\n\n✨ پرامپت " . $this->fa($optimized) . ' اسلاید با پرامپت اصلاح‌گر اصلاح شد.';
        }
        if (! empty($ai['duplicate_product_name'])) {
            $text .= "\n\n⚠️ محصولی با نام مشابه وجود دارد: " . $this->e($ai['duplicate_product_name'])
                . (! empty($ai['duplicate_product_code']) ? ' (کد ' . $this->e($ai['duplicate_product_code']) . ')' : '')
                . '. اگر محصول جدیدی است ادامه بده؛ وگرنه نام را اصلاح کن.';
        }
        if (! app(ProductShotFeature::class)->enabled()) {
            $text .= "\n\nℹ️ «استودیو محصول» در سایت خاموش است؛ محصول ثبت می‌شود ولی تا روشن شدن از پنل برای کاربران دیده نمی‌شود.";
        }
        if ($notice) {
            $text .= "\n\n" . $this->e($notice);
        }

        $manager = $draft->manager;
        $first = [['text' => '💾 ذخیره در پیش‌نویس', 'callback_data' => 'ps:save:draft']];
        if (! $manager || $manager->can('publish_product')) {
            array_unshift($first, ['text' => '🚀 انتشار در سایت', 'callback_data' => 'ps:save:active']);
        }

        return $this->response($draft->chat_id, $text, [
            $first,
            [['text' => '✏️ نام', 'callback_data' => 'ps:ed:name_fa'], ['text' => '✏️ نام انگلیسی', 'callback_data' => 'ps:ed:name_en'], ['text' => '✏️ توضیح', 'callback_data' => 'ps:ed:description_fa']],
            [['text' => '🏷 صنف', 'callback_data' => 'ps:rv:occ'], ['text' => '🗂 دسته', 'callback_data' => 'ps:rv:cat'], ['text' => '🖼 اسلایدها', 'callback_data' => 'ps:rv:slides']],
            [['text' => 'لغو فرآیند', 'callback_data' => 'ps:x']],
        ], ['status' => 'ps_review', 'draft_id' => $draft->id, 'photo_url' => $this->coverUrl($draft)]);
    }

    private function editPrompt(TelegramProductDraft $draft, string $field): array
    {
        $label = self::EDIT_FIELDS[$field] ?? null;
        $current = '';
        if (Str::startsWith($field, 'slide:')) {
            $index = (int) Str::after($field, 'slide:');
            $slides = $this->completeSlides($draft);
            if (! isset($slides[$index])) {
                return $this->slideList($draft);
            }
            $label = 'نام اسلاید ' . $this->fa($index + 1);
            $current = (string) ($slides[$index]['name'] ?? '');
        } elseif ($label === null || ! $draft->ai_result) {
            return $this->review($draft);
        } else {
            $current = (string) data_get($draft->ai_result, $field, '');
        }
        $draft->forceFill(['state' => 'ps_edit', 'pending_edit_field' => $field])->save();

        return $this->response($draft->chat_id, "✏️ <b>{$label}</b>\n\nمتن فعلی (برای کپی):\n<code>" . $this->e($current) . "</code>\n\n👇 متن جدید را در یک پیام بفرست.", [
            [['text' => '⬅️ انصراف', 'callback_data' => Str::startsWith($field, 'slide:') ? 'ps:rv:slides' : 'ps:rv:back']],
        ], ['status' => 'ps_edit', 'draft_id' => $draft->id]);
    }

    private function applyEdit(TelegramProductDraft $draft, string $text): array
    {
        $field = (string) $draft->pending_edit_field;
        if ($text === '') {
            return $this->editPrompt($draft, $field);
        }
        if (Str::startsWith($field, 'slide:')) {
            $index = (int) Str::after($field, 'slide:');
            $payload = (array) $draft->input_payload;
            $slides = array_values((array) data_get($payload, 'pshot.slides', []));
            $complete = array_keys(array_filter($slides, fn ($s) => $this->isComplete($s)));
            if (isset($complete[$index])) {
                $slides[$complete[$index]]['name'] = Str::limit($text, 120, '');
                data_set($payload, 'pshot.slides', $slides);
            }
            $draft->forceFill(['input_payload' => $payload, 'state' => 'ps_slide_list', 'pending_edit_field' => null])->save();

            return $this->slideList($draft->fresh(), 'نام اسلاید به‌روز شد.');
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

        return $this->review($draft, '✅ ' . (self::EDIT_FIELDS[$field] ?? 'متن') . ' به‌روز شد.');
    }

    // ───────────────────────── فهرست اسلایدها (در بازبینی) ─────────────────────────

    private function slideList(TelegramProductDraft $draft, ?string $notice = null): array
    {
        $draft->forceFill(['state' => 'ps_slide_list', 'pending_edit_field' => null])->save();
        $slides = $this->completeSlides($draft);
        $rows = [];
        foreach ($slides as $i => $slide) {
            $rows[] = [
                ['text' => '✏️ ' . $this->fa($i + 1) . '. ' . Str::limit((string) ($slide['name'] ?? ''), 24, '…'), 'callback_data' => 'ps:sn:' . $i],
                ['text' => '🗑', 'callback_data' => 'ps:sd:' . $i],
            ];
        }
        if (count($slides) < $this->settings->maxSlides()) {
            $rows[] = [['text' => '➕ افزودن اسلاید', 'callback_data' => 'ps:sl:add']];
        }
        $rows[] = [['text' => '⬅️ بازبینی', 'callback_data' => 'ps:rv:back']];
        $text = "🖼 <b>اسلایدها</b>\n\nبرای تغییر نام روی اسلاید بزن؛ 🗑 اسلاید را حذف می‌کند. ترتیب نمایش و پرامپت‌ها بعداً از «ویرایش کامل در داشبورد» هم قابل تغییر است.";
        if ($notice) {
            $text .= "\n\n" . $this->e($notice);
        }

        return $this->response($draft->chat_id, $text, $rows, ['status' => 'ps_slide_list', 'draft_id' => $draft->id]);
    }

    private function deleteSlide(TelegramProductDraft $draft, int $index): array
    {
        $payload = (array) $draft->input_payload;
        $slides = array_values((array) data_get($payload, 'pshot.slides', []));
        $complete = array_keys(array_filter($slides, fn ($s) => $this->isComplete($s)));
        if (count($complete) <= 1) {
            return $this->slideList($draft, '⚠️ حداقل یک اسلاید لازم است.');
        }
        if (isset($complete[$index])) {
            $removed = $slides[$complete[$index]];
            array_splice($slides, $complete[$index], 1);
            data_set($payload, 'pshot.slides', array_values($slides));
            $draft->forceFill(['input_payload' => $payload])->save();
            $this->deleteFiles([$removed['image'] ?? null]);
        }

        return $this->slideList($draft->fresh(), 'اسلاید حذف شد.');
    }

    private function addSlides(TelegramProductDraft $draft): array
    {
        $payload = (array) $draft->input_payload;
        $slides = array_values((array) data_get($payload, 'pshot.slides', []));
        if (count($slides) >= $this->settings->maxSlides()) {
            return $this->slideList($draft, '⚠️ سقف اسلایدها پر شده است.');
        }
        $slides[] = $this->newSlide();
        data_set($payload, 'pshot.slides', $slides);
        $draft->forceFill(['input_payload' => $payload, 'state' => 'ps_slides'])->save();

        return $this->slidesPanel($draft->fresh());
    }

    // ───────────────────────── صنف ─────────────────────────

    private function groupPrompt(TelegramProductDraft $draft): array
    {
        $draft->forceFill(['state' => 'ps_group'])->save();
        $selected = $this->selectedOccupations($draft);
        $selectedGroups = $this->registrar->groupsOfOccupations($selected->pluck('id')->all());
        $buttons = [];
        foreach ($this->registrar->groups() as $key => $label) {
            $buttons[] = ['text' => (in_array($key, $selectedGroups, true) ? '✅ ' : '') . $label, 'callback_data' => 'ps:g:' . $key];
        }
        $rows = array_chunk($buttons, 2);
        $rows[] = [['text' => '✅ تأیید و برگشت به بازبینی', 'callback_data' => 'ps:g:done']];
        $text = "🏷 <b>صنف‌های مناسب این محصول</b>\n\nپیشنهاد هوش مصنوعی از قبل تیک خورده است. گروه را بزن تا شغل‌ها را تیک بزنی یا برداری.";
        $text .= "\n\nانتخاب‌شده: " . $this->e($selected->pluck('name_fa')->implode('، ') ?: '—');

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
        $rows[] = [['text' => '⬅️ گروه‌ها', 'callback_data' => 'ps:rv:occ'], ['text' => '✅ تأیید', 'callback_data' => 'ps:g:done']];
        $label = Occupation::GROUPS[$group] ?? $group;

        return $this->response($draft->chat_id, "🏷 <b>{$label}</b>\n\nشغل‌هایی که این محصول برایشان مناسب است را تیک بزن:", $rows, [
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
        $rows[] = [['text' => '✅ تأیید و برگشت به بازبینی', 'callback_data' => 'ps:c:done']];
        $names = $all->whereIn('id', $selected)->map(fn ($c) => $c->name_fa ?: $c->name)->implode('، ');
        $text = "🗂 <b>دسته‌بندی محصول در سایت</b>\n\nپیشنهاد هوش مصنوعی از قبل تیک خورده است. برای انتشار حداقل یک دسته لازم است.\n\nانتخاب‌شده: " . $this->e($names ?: '—');
        if ($notice) {
            $text .= "\n\n⚠️ " . $this->e($notice);
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

    // ───────────────────────── قاب‌های اکسپلور و ثبت ─────────────────────────

    private function tilesPrompt(TelegramProductDraft $draft, ?string $notice = null): array
    {
        $draft->forceFill(['state' => 'ps_tiles'])->save();
        $selected = $this->selectedTiles($draft);
        $buttons = [];
        foreach (ProductShotBotSettings::TILES as $key => $label) {
            $buttons[] = ['text' => (in_array($key, $selected, true) ? '✅ ' : '⬜ ') . $label, 'callback_data' => 'ps:t:' . $key];
        }
        $rows = array_chunk($buttons, 2);
        $rows[] = [['text' => '🚀 ثبت و انتشار', 'callback_data' => 'ps:t:done']];
        $rows[] = [['text' => '⬅️ بازبینی', 'callback_data' => 'ps:rv:back']];
        $text = "🖼 <b>قاب نمایش در هوم و اکسپلور</b>\n\nقاب‌هایی که این محصول اجازه‌ی نمایش در آن‌ها را دارد روشن کن (حداقل یکی).";
        if ($notice) {
            $text .= "\n\n⚠️ " . $this->e($notice);
        }

        return $this->response($draft->chat_id, $text, $rows, ['status' => 'ps_tiles', 'draft_id' => $draft->id]);
    }

    private function toggleTile(TelegramProductDraft $draft, string $key): array
    {
        $selected = $this->selectedTiles($draft);
        $notice = null;
        if (array_key_exists($key, ProductShotBotSettings::TILES)) {
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
        data_set($payload, 'pshot.tiles', array_values(array_intersect(array_keys(ProductShotBotSettings::TILES), $selected)));
        $draft->forceFill(['input_payload' => $payload])->save();

        return $this->tilesPrompt($draft->fresh(), $notice);
    }

    private function save(TelegramProductDraft $draft, TelegramProductManager $manager, string $status): array
    {
        if (! in_array($draft->state, ['ps_review', 'ps_tiles'], true)) {
            return $this->current($draft, 'این مرحله دیگر برای ثبت آماده نیست.');
        }
        if ($status === 'active' && ! $manager->can('publish_product')) {
            return $this->review($draft, '⚠️ دسترسی انتشار برای حساب شما فعال نیست؛ می‌توانی در پیش‌نویس ذخیره کنی.');
        }
        if ($this->selectedOccupations($draft)->isEmpty()) {
            return $this->groupPrompt($draft);
        }
        if ($status === 'active' && $this->selectedCategories($draft)->isEmpty()) {
            return $this->categoriesPrompt($draft, 0, 'برای انتشار حداقل یک دسته‌بندی انتخاب کن.');
        }
        $ai = (array) $draft->ai_result;
        $data = (array) data_get($draft->input_payload, 'pshot', []);
        $slides = $this->completeSlides($draft);
        $credits = $this->settings->slideCredits();
        $customSlides = collect($slides)->values()->map(fn ($slide, $i) => [
            'name' => (string) ($slide['name'] ?? ('اسلاید ' . ($i + 1))),
            'prompt' => (string) ($slide['prompt'] ?? $this->joinParts($slide)),
            'original_prompt' => $this->joinParts($slide),
            'sample_path' => $slide['image'] ?? null,
            'enabled' => true,
            'sort' => $i,
            'credits' => $credits,
            'allowed_aspect_ratios' => (array) $this->settings->get('slides.allowed_aspect_ratios', []),
            'aspect_ratio_default' => (string) $this->settings->get('slides.aspect_ratio_default', '4:5'),
            'aspect_ratio_user_selectable' => (bool) $this->settings->get('slides.aspect_ratio_user_selectable', true),
        ])->all();

        $quality = (array) $this->settings->get('quality', []);
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
                // کاور = نمونه‌ی اسلاید اول (سازنده خودش از اسلایدها برمی‌دارد)
                'image_paths' => [],
                'shots' => [],
                'custom_slides' => $customSlides,
                'input_image' => $data['input_image'] ?? null,
                'quality_models' => $this->settings->qualityModels(),
                'preflight' => [
                    'enabled' => (bool) ($quality['preflight_enabled'] ?? true),
                    'blocking_issues' => (array) ($quality['blocking_issues'] ?? []),
                    'min_side' => (int) ($quality['min_side'] ?? 900),
                    'product_sheet_enabled' => (bool) ($quality['product_sheet_enabled'] ?? true),
                    'product_sheet_size' => (int) ($quality['product_sheet_size'] ?? 2048),
                ],
                'display' => (array) $this->settings->get('display', []),
                'explore_tiles' => $status === 'active' ? $this->selectedTiles($draft) : (array) $this->settings->get('display.explore_tiles', ['1x1']),
                'created_by' => $manager->admin_id,
            ]);
        } catch (\RuntimeException $exception) {
            return $this->review($draft, '⚠️ ' . $exception->getMessage());
        } catch (\Throwable $exception) {
            Log::error('telegram product shot save failed', ['draft' => $draft->id, 'error' => $exception->getMessage()]);

            return $this->review($draft, '⚠️ ثبت محصول با خطا روبه‌رو شد؛ چند لحظه بعد دوباره امتحان کن.');
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
                'input_prompt' => Str::limit((string) $draft->description, 60000, ''),
                'ai_result' => array_merge($ai, ['product_mode' => 'product', 'slides' => count($customSlides)]),
                'status' => $status,
            ]);
        }

        $buttons = [];
        if ($status === 'active' && $product->short_code) {
            $buttons[] = [['text' => '🌐 مشاهده در سایت', 'url' => route('app.pro', ['product' => $product->short_code])]];
        }
        $buttons[] = [['text' => '🛠 ویرایش کامل در داشبورد', 'url' => route('admin.product-shots.products.create', ['product' => $product->id])]];
        $buttons[] = [['text' => '➕ ثبت محصول بعدی', 'callback_data' => 'product:start']];
        $text = $this->settings->text($status === 'active' ? 'final_published' : 'final_draft')
            . "\n\nکد محصول: <code>" . $this->e((string) $product->product_code) . '</code>'
            . "\nنام: " . $this->e((string) $product->name_fa)
            . "\nاسلایدها: " . $this->fa(count($customSlides));

        return $this->response($draft->chat_id, $text, $buttons, [
            'status' => $product->status,
            'draft_id' => $draft->id,
            'product_id' => $product->id,
            'final_product' => true,
            'photo_url' => $this->coverUrl($draft),
            // فقط پیام ثبت نهایی در چت می‌ماند.
            'delete_message_ids' => array_values(array_filter(array_map('strval', (array) $draft->message_ids))),
        ]);
    }

    // ───────────────────────── مسیریابی ─────────────────────────

    private function callback(TelegramProductManager $manager, TelegramProductDraft $draft, string $chatId, string $callback): array
    {
        $parts = explode(':', $callback, 3);
        $section = $parts[1] ?? '';
        $value = $parts[2] ?? '';
        $hasAi = (bool) $draft->ai_result && $draft->state !== 'processing';

        return match (true) {
            $section === 'status' => $this->resultResponse($draft),
            $section === 'retry' => $draft->state === 'failed' ? $this->beginProcessing($draft) : $this->resultResponse($draft),
            $section === 'sl' && $value === 'add' => $hasAi ? $this->addSlides($draft) : $this->current($draft, null),
            $section === 'sl' => $this->slideAction($draft, $value),
            $section === 'rv' && $value === 'back' => $hasAi ? $this->review($draft) : $this->current($draft, null),
            $section === 'rv' && $value === 'occ' => $hasAi ? $this->groupPrompt($draft) : $this->current($draft, null),
            $section === 'rv' && $value === 'cat' => $hasAi ? $this->categoriesPrompt($draft, 0) : $this->current($draft, null),
            $section === 'rv' && $value === 'slides' => $hasAi ? $this->slideList($draft) : $this->current($draft, null),
            $section === 'ed' => $hasAi ? $this->editPrompt($draft, $value) : $this->current($draft, null),
            $section === 'sn' => $hasAi ? $this->editPrompt($draft, 'slide:' . (int) $value) : $this->current($draft, null),
            $section === 'sd' => $hasAi && $draft->state === 'ps_slide_list' ? $this->deleteSlide($draft, (int) $value) : $this->current($draft, null),
            $section === 'g' && $value === 'done' => $hasAi ? ($this->selectedOccupations($draft)->isEmpty() ? $this->groupPrompt($draft) : $this->review($draft)) : $this->current($draft, null),
            $section === 'g' => $hasAi ? $this->occupationsPrompt($draft, $value) : $this->current($draft, null),
            $section === 'o' => $draft->state === 'ps_occupations' ? $this->toggleOccupation($draft, $value) : $this->current($draft, null),
            $section === 'c' && $value === 'done' => $draft->state === 'ps_categories' ? $this->review($draft) : $this->current($draft, null),
            $section === 'c' => $draft->state === 'ps_categories' ? $this->toggleCategory($draft, (int) $value) : $this->current($draft, null),
            $section === 'cp' => $draft->state === 'ps_categories' ? $this->categoriesPrompt($draft, (int) $value) : $this->current($draft, null),
            $section === 'save' && $value === 'active' => $draft->state === 'ps_review' ? $this->beforePublish($draft, $manager) : $this->current($draft, null),
            $section === 'save' => $this->save($draft, $manager, 'draft'),
            $section === 't' && $value === 'done' => $draft->state === 'ps_tiles' ? $this->save($draft, $manager, 'active') : $this->current($draft, null),
            $section === 't' => $draft->state === 'ps_tiles' ? $this->toggleTile($draft, $value) : $this->current($draft, null),
            $section === 'back' && $value === 'slides' => in_array($draft->state, ['failed', 'ps_slides'], true) ? $this->slidesPanel($draft) : $this->current($draft, null),
            default => $this->current($draft, 'این دکمه دیگر معتبر نیست.'),
        };
    }

    private function beforePublish(TelegramProductDraft $draft, TelegramProductManager $manager): array
    {
        if (! $manager->can('publish_product')) {
            return $this->review($draft, '⚠️ دسترسی انتشار برای حساب شما فعال نیست؛ می‌توانی در پیش‌نویس ذخیره کنی.');
        }
        if ($this->selectedOccupations($draft)->isEmpty()) {
            return $this->groupPrompt($draft);
        }
        if ($this->selectedCategories($draft)->isEmpty()) {
            return $this->categoriesPrompt($draft, 0, 'برای انتشار حداقل یک دسته‌بندی انتخاب کن.');
        }

        return $this->tilesPrompt($draft);
    }

    private function text(TelegramProductDraft $draft, string $text, array $input): array
    {
        if ($text === '') {
            return $this->current($draft, null);
        }

        return match ($draft->state) {
            'ps_slides' => $this->receivePromptPart($draft, $text, $input),
            'ps_edit' => $this->applyEdit($draft, $text),
            'ps_input' => $this->inputPrompt($draft, 'اول عکس ورودی را بفرست (عکس، نه متن).'),
            default => $this->current($draft, 'لطفاً از دکمه‌های پیام استفاده کن.'),
        };
    }

    /** نمایش دوباره‌ی همان مرحله‌ای که درفت در آن است. */
    private function current(TelegramProductDraft $draft, ?string $notice): array
    {
        $response = match ($draft->state) {
            'ps_input' => $this->inputPrompt($draft, $notice),
            'ps_slides' => $this->slidesPanel($draft, $notice),
            'processing', 'failed' => $this->resultResponse($draft),
            'ps_review' => $this->review($draft),
            'ps_edit' => $this->editPrompt($draft, (string) $draft->pending_edit_field),
            'ps_group' => $this->groupPrompt($draft),
            'ps_occupations' => $this->occupationsPrompt($draft, (string) data_get($draft->input_payload, 'pshot.group', '')),
            'ps_categories' => $this->categoriesPrompt($draft, (int) data_get($draft->input_payload, 'pshot.category_page', 0), $notice),
            'ps_slide_list' => $this->slideList($draft),
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
        $data = (array) data_get($draft->input_payload, 'pshot', []);
        $this->deleteFiles(array_merge([$data['input_image'] ?? null], collect((array) ($data['slides'] ?? []))->pluck('image')->all()));

        return $this->response($chatId, 'فرآیند ثبت محصول پروداکتی لغو شد؛ هیچ محصولی ثبت نشد.', [], [
            'status' => 'cancelled',
            'draft_id' => $draft->id,
            'delete_message_ids' => array_values(array_filter(array_map('strval', (array) $draft->message_ids))),
            'reply_markup' => [
                'keyboard' => [[['text' => 'ثبت محصول'], ['text' => 'آموزش']]],
                'resize_keyboard' => true,
                'is_persistent' => true,
            ],
        ]);
    }

    // ───────────────────────── کمکی‌ها ─────────────────────────

    private function newSlide(): array
    {
        return ['id' => Str::lower(Str::random(8)), 'image' => null, 'parts' => [], 'name' => null, 'done' => false];
    }

    private function slides(TelegramProductDraft $draft): array
    {
        return array_values((array) data_get($draft->input_payload, 'pshot.slides', []));
    }

    /** اسلایدهای کامل (عکس + پرامپت) به ترتیب. */
    private function completeSlides(TelegramProductDraft $draft): array
    {
        return array_values(array_filter($this->slides($draft), fn ($s) => $this->isComplete($s)));
    }

    /** اسلایدی که در حال ساخت است (آخرین اسلایدی که «ثبت» نشده). */
    private function currentIndex(array $slides): ?int
    {
        if ($slides === []) {
            return null;
        }
        $last = count($slides) - 1;

        return empty($slides[$last]['done']) ? $last : null;
    }

    private function isComplete(array $slide): bool
    {
        return ! empty($slide['image']) && $this->parts($slide) !== [];
    }

    private function parts(array $slide): array
    {
        return array_values(array_filter((array) ($slide['parts'] ?? []), fn ($p) => trim((string) ($p['t'] ?? '')) !== ''));
    }

    /** تکه‌های پرامپت به ترتیب شناسه‌ی پیام تلگرام (پیام‌های بلند ممکن است جابه‌جا برسند). */
    private function joinParts(array $slide): string
    {
        $parts = $this->parts($slide);
        $indexed = [];
        foreach ($parts as $i => $part) {
            $indexed[] = ['m' => (int) ($part['m'] ?? 0), 'i' => $i, 't' => (string) $part['t']];
        }
        usort($indexed, fn ($a, $b) => ($a['m'] === 0 || $b['m'] === 0) ? $a['i'] <=> $b['i'] : [$a['m'], $a['i']] <=> [$b['m'], $b['i']]);

        return trim(implode("\n", array_map(fn ($p) => trim($p['t']), $indexed)));
    }

    private function slidesDigest(TelegramProductDraft $draft): string
    {
        return collect($this->completeSlides($draft))->values()
            ->map(fn ($s, $i) => '[اسلاید ' . ($i + 1) . "]\n" . $this->joinParts($s))
            ->implode("\n\n");
    }

    private function aiModel(): string
    {
        return trim((string) $this->settings->get('ai.model', '')) ?: (string) config('services.telegram_product.ai_model', 'openai/gpt-4o-mini');
    }

    private function selectedOccupations(TelegramProductDraft $draft): Collection
    {
        $ids = array_map('intval', (array) data_get($draft->input_payload, 'pshot.occupation_ids', []));

        return $ids === [] ? collect() : Occupation::query()->whereIn('id', $ids)->ordered()->get(['id', 'name_fa', 'group_key']);
    }

    private function selectedCategories(TelegramProductDraft $draft): Collection
    {
        $ids = array_map('intval', (array) data_get($draft->input_payload, 'pshot.category_ids', []));

        return $ids === [] ? collect() : Category::query()->whereIn('id', $ids)->get(['id', 'name', 'name_fa']);
    }

    private function selectedTiles(TelegramProductDraft $draft): array
    {
        $saved = data_get($draft->input_payload, 'pshot.tiles');
        $tiles = array_values(array_intersect(array_keys(ProductShotBotSettings::TILES), (array) ($saved ?? $this->settings->get('display.explore_tiles', ['1x1']))));

        return $tiles ?: ['1x1'];
    }

    private function coverUrl(TelegramProductDraft $draft): ?string
    {
        $path = collect($this->completeSlides($draft))->pluck('image')->filter()->first();

        return $path ? Storage::disk('public')->url((string) $path) : null;
    }

    private function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_string($path) && Str::startsWith($path, [self::INPUT_DIR . '/', self::SAMPLE_DIR . '/'])) {
                try {
                    Storage::disk('public')->delete($path);
                } catch (\Throwable) {
                    // پاک‌سازی کمکی است.
                }
            }
        }
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
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function fa(int|string $number): string
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
