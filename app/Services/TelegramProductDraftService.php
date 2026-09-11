<?php

namespace App\Services;

use App\Jobs\ProcessTelegramProductDraftJob;
use App\Models\AiModel;
use App\Models\Category;
use App\Models\Product;
use App\Models\TelegramProductDraft;
use App\Models\TelegramProductEvent;
use App\Models\TelegramProductManager;
use App\Models\TelegramProductRegistration;
use App\Models\TelegramProductSetting;
use App\Support\ProviderStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TelegramProductDraftService
{
    public function __construct(
        private readonly ProductImageOptimizer $images,
        private readonly OpenRouterService $openRouter,
    ) {
    }

    public function handle(array $input, ?UploadedFile $image = null): array
    {
        $telegramId = (int) data_get($input, 'telegram.id', data_get($input, 'telegram.telegram_id', $input['telegram_id'] ?? 0));
        $chatId = (string) ($input['chat_id'] ?? data_get($input, 'telegram.chat_id', $telegramId));
        $updateId = isset($input['update_id']) ? (int) $input['update_id'] : null;
        $eventType = $this->eventType($input, $image);

        $manager = TelegramProductManager::query()->active()->where('telegram_id', $telegramId)->first();
        if (! $manager) {
            return $this->response($chatId, 'این بات فقط برای مدیران مجاز ثبت محصول فعال است.', [
                ['text' => 'راهنمای دسترسی', 'callback_data' => 'product:access'],
            ], ['status' => 'forbidden']);
        }

        if ($updateId !== null) {
            $duplicate = TelegramProductEvent::query()->where('update_id', $updateId)->first();
            if ($duplicate?->response) {
                return array_merge($duplicate->response, ['duplicate' => true]);
            }
        }

        $draft = $this->activeDraft($manager, $chatId);
        if ($draft && $eventType === 'callback') {
            $this->rememberMessage($draft, $input['message_id'] ?? null);
        }
        $response = match ($eventType) {
            'start' => $this->start($manager, $chatId, $input),
            'photo' => $this->photo($manager, $draft, $chatId, $input, $image),
            'callback' => $this->callback($manager, $draft, $chatId, $input),
            default => $this->text($manager, $draft, $chatId, $input),
        };

        if ($updateId !== null) {
            TelegramProductEvent::query()->create([
                'update_id' => $updateId,
                'telegram_product_manager_id' => $manager->id,
                'draft_id' => $response['draft_id'] ?? $draft?->id,
                'event_type' => $eventType,
                'payload' => $this->safePayload($input),
                'response' => $response,
                'processed_at' => now(),
            ]);
        }

        return $response;
    }

    public function status(TelegramProductDraft $draft): array
    {
        if ($draft->state === 'processing') {
            return ['ok' => true, 'status' => 'processing', 'ready' => false, 'draft_id' => $draft->id];
        }

        if (in_array($draft->state, ['review', 'duplicate'], true) && ! $draft->reviewed_at) {
            $draft->forceFill(['reviewed_at' => now()])->save();
            return $this->reviewResponse($draft);
        }

        return [
            'ok' => true,
            'status' => (string) $draft->state,
            'ready' => in_array($draft->state, ['review', 'duplicate'], true),
            'duplicate' => true,
            'draft_id' => $draft->id,
        ];
    }

    public function process(TelegramProductDraft $draft): void
    {
        $draft->refresh();
        if ($draft->state !== 'processing') {
            return;
        }

        try {
            $categories = Category::query()->active()->orderBy('name_fa')->get();
            $ai = $this->openRouter->generateProductMetadata(
                (string) $draft->description,
                $categories->map(fn (Category $category) => $category->name_fa ?: $category->name)->all(),
            );
            $category = $this->matchCategory((string) $ai['category'], $categories);
            $duplicate = $this->duplicateProduct($ai['name_fa'], $ai['name_en']);
            $ai['category_id'] = $category?->id;
            $ai['category_name'] = $category?->name;
            if ($duplicate) {
                $ai['duplicate_product_id'] = $duplicate->id;
                $ai['duplicate_product_name'] = $duplicate->name_fa ?: $duplicate->name_en;
            }

            $draft->forceFill([
                'ai_result' => $ai,
                'state' => $duplicate ? 'duplicate' : 'review',
                'error_message' => null,
            ])->save();
        } catch (\Throwable $exception) {
            $draft->forceFill([
                'state' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 1000, ''),
            ])->save();
        }
    }

    private function start(TelegramProductManager $manager, string $chatId, array $input): array
    {
        if (trim((string) ($input['text'] ?? '')) === '/start') {
            return $this->response($chatId, "سلام عزیز، خوش اومدی به سیستم\nهوشمند ثبت محصول پلتفرم وطن", [
                ['text' => 'ثبت محصول جدید', 'callback_data' => 'product:start'],
                ['text' => 'ویرایش محصولات', 'callback_data' => 'product:edit'],
            ], [
                'status' => 'welcome',
                'welcome' => true,
                'photo_url' => 'https://placehold.co/1200x630/0d1b2a/ffffff.png?text=Vatan',
                'reply_markup' => [
                    'inline_keyboard' => [[
                        ['text' => 'ثبت محصول جدید', 'callback_data' => 'product:start'],
                        ['text' => 'ویرایش محصولات', 'callback_data' => 'product:edit'],
                    ]],
                ],
            ]);
        }

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
            'state' => 'awaiting_image',
            'input_payload' => $this->safePayload($input),
        ]);
        $this->rememberMessage($draft, $input['message_id'] ?? null);

        return $this->response($chatId, "خب بیا ثبت محصول جدید‌رو شروع کنیم\nقدم اول برام تصاویر اصلی محصول‌رو ارسال کن...", [
            ['text' => 'لغو فرآیند', 'callback_data' => 'product:cancel'],
        ], [
            'status' => 'awaiting_image',
            'draft_id' => $draft->id,
            'delete_message_ids' => $this->messageIds($draft),
            'reply_markup' => $this->mainMenuMarkup(),
        ]);
    }

    private function photo(TelegramProductManager $manager, ?TelegramProductDraft $draft, string $chatId, array $input, ?UploadedFile $image): array
    {
        if (! $draft || ! in_array($draft->state, ['awaiting_image', 'awaiting_prompt'], true)) {
            return $this->response($chatId, 'ابتدا روی «ثبت محصول جدید» یا دستور شروع بزنید.', [], ['status' => 'no_draft']);
        }

        $paths = (array) $draft->image_paths;
        if (count($paths) >= (int) config('services.telegram_product.max_images', 5)) {
            return $this->response($chatId, 'تعداد تصویرها به سقف مجاز رسیده است. برای ادامه روی دکمه‌ی زیر بزنید.', [
                ['text' => 'ادامه با همین تصویرها', 'callback_data' => 'product:describe'],
            ], ['status' => 'awaiting_description', 'draft_id' => $draft->id]);
        }
        if (! $image && filled($input['file_id'] ?? null)) {
            $image = $this->downloadTelegramImage((string) $input['file_id']);
        }
        if (! $image) {
            throw ValidationException::withMessages(['image' => 'فایل تصویر به بک‌اند تحویل داده نشده است.']);
        }

        $hasPreviousImage = $paths !== [];
        $paths[] = $this->images->store($image, 'products/telegram');
        $draft->forceFill(['image_paths' => array_values($paths), 'state' => 'awaiting_prompt'])->save();
        $this->rememberMessage($draft, $input['message_id'] ?? null);

        $text = $hasPreviousImage
            ? "محتوای دیگه از این محصول دریافت شد ✅\nمرسی که دقت نظر داری و محتوای بیشتری می‌فرستی برام\nخب حالا پرامپت ساخت این محصول‌رو همراه با نام و توضیحات دلخواهت در یک پیام بفرست برام"
            : "محتوا دریافت شد ✅\nچه عکس خفنی داره محصولی که انتخاب کردی، ایول...!\nخب حالا پرامپت ساخت این محصول‌رو\nهمراه با نام و توضیحات دلخواهت در یک پیام بفرست برام";

        return $this->response($chatId, $text, [
            ['text' => 'لغو فرآیند', 'callback_data' => 'product:cancel'],
        ], ['status' => 'awaiting_prompt', 'draft_id' => $draft->id, 'delete_message_ids' => $this->messageIds($draft), 'reply_markup' => $this->mainMenuMarkup()]);
    }

    private function downloadTelegramImage(string $fileId): UploadedFile
    {
        $token = trim((string) config('services.telegram_product.bot_token'));
        if ($token === '') {
            throw new \RuntimeException('توکن بات اختصاصی ثبت محصول تنظیم نشده است.');
        }

        $fileResponse = Http::timeout(20)->get("https://api.telegram.org/bot{$token}/getFile", ['file_id' => $fileId]);
        $fileResponse->throw();
        $filePath = (string) $fileResponse->json('result.file_path');
        if ($filePath === '') {
            throw new \RuntimeException('مسیر تصویر از تلگرام دریافت نشد.');
        }

        $imageResponse = Http::timeout(30)->get("https://api.telegram.org/file/bot{$token}/{$filePath}");
        $imageResponse->throw();
        $temporaryPath = tempnam(sys_get_temp_dir(), 'vatan-telegram-');
        if ($temporaryPath === false || file_put_contents($temporaryPath, $imageResponse->body()) === false) {
            throw new \RuntimeException('ذخیره‌ی موقت تصویر تلگرام ناموفق بود.');
        }

        $mime = $imageResponse->header('Content-Type') ?: 'image/jpeg';
        return new class($temporaryPath, $mime) extends UploadedFile {
            public function __construct(private readonly string $temporaryPath, string $mime)
            {
                parent::__construct($temporaryPath, 'telegram-image', $mime, null, true);
            }

            public function __destruct()
            {
                if (is_file($this->temporaryPath)) {
                    @unlink($this->temporaryPath);
                }
            }
        };
    }

    private function callback(TelegramProductManager $manager, ?TelegramProductDraft $draft, string $chatId, array $input): array
    {
        $callback = (string) ($input['callback_data'] ?? '');
        $action = explode(':', $callback, 3);
        if (($action[1] ?? '') === 'start') {
            return $this->start($manager, $chatId, $input);
        }
        if (($action[1] ?? '') === 'settings') {
            return $this->settingsAction($manager, $chatId, $action[2] ?? '');
        }
        if (($action[1] ?? '') === 'education') {
            return $this->education($chatId);
        }
        if (($action[1] ?? '') === 'edit' && blank($action[2] ?? '')) {
            if (! $manager->can('edit_product')) {
                return $this->response($chatId, 'دسترسی ویرایش محصول برای حساب شما فعال نیست.', [], ['status' => 'forbidden']);
            }

            return $this->beginProductEdit($manager, $draft, $chatId, $input);
        }
        if (! $draft) {
            return $this->response($chatId, 'فرآیند فعالی وجود ندارد. ابتدا ثبت محصول جدید را شروع کنید.');
        }

        return match ($action[1] ?? '') {
            'add_image' => $this->setStateResponse($draft, 'awaiting_image', $chatId, 'تصویر بعدی را ارسال کنید.'),
            'describe' => $this->setStateResponse($draft, 'awaiting_prompt', $chatId, 'پرامپت ساخت محصول را همراه با نام و توضیحات در یک پیام بفرستید.'),
            'status' => $this->statusResponse($draft, $chatId),
            'cancel' => $this->cancel($draft, $chatId),
            'cancel_edit' => $this->cancelEdit($draft, $chatId),
            'edit' => $this->editPrompt($draft, $chatId, $action[2] ?? ''),
            'confirm' => $this->confirmMetadata($draft, $chatId),
            'save_draft' => $this->save($draft, 'draft', $chatId),
            'publish' => $manager->can('publish_product') ? $this->save($draft, 'active', $chatId) : $this->response($chatId, 'دسترسی انتشار محصول برای حساب شما فعال نیست.', [], ['status' => 'forbidden']),
            default => $this->response($chatId, 'این دکمه دیگر معتبر نیست. لطفاً وضعیت فعلی را دوباره بررسی کنید.'),
        };
    }

    private function text(TelegramProductManager $manager, ?TelegramProductDraft $draft, string $chatId, array $input): array
    {
        $text = trim((string) ($input['text'] ?? ''));
        if ($text === 'ثبت محصول جدید') {
            return $this->start($manager, $chatId, $input);
        }
        if ($text === 'ویرایش محصول') {
            if (! $manager->can('edit_product')) return $this->response($chatId, 'دسترسی ویرایش محصول برای حساب شما فعال نیست.', [], ['status' => 'forbidden']);
            return $this->beginProductEdit($manager, $draft, $chatId, $input);
        }
        if ($text === 'تنظیمات') return $this->settingsMenu($manager, $chatId);
        if ($text === 'آموزش') return $this->education($chatId);
        if ($text === 'دریافت نتیجه' && $draft) return $this->statusResponse($draft, $chatId);
        if (in_array($text, ['تنظیمات پرامپت اسم و توضیحات محصول', 'تنظیمات پرامپت مادر محصول'], true)) return $this->settingsAction($manager, $chatId, 'metadata');
        if ($text === 'تنظیمات پرامپت اصلاح پرامپت محصول') return $this->settingsAction($manager, $chatId, 'optimizer');
        if ($text === 'لغو فرآیند' && $draft) {
            return $this->cancel($draft, $chatId);
        }
        if (! $draft) {
            return $this->response($chatId, 'برای ثبت محصول، یکی از گزینه‌های منوی اصلی را انتخاب کنید.', [], [
                'status' => 'no_draft',
                'reply_markup' => $this->mainMenuMarkup(),
            ]);
        }
        if ($text === 'تأیید' && in_array($draft->state, ['review', 'duplicate'], true)) return $this->confirmMetadata($draft, $chatId);
        if ($text === 'کپی برای ویرایش' && in_array($draft->state, ['review', 'duplicate', 'awaiting_save_choice'], true)) return $this->editPrompt($draft, $chatId, 'metadata');
        if ($text === 'ذخیره در پیش‌نویس' && in_array($draft->state, ['review', 'awaiting_save_choice'], true)) return $this->save($draft, 'draft', $chatId);
        if (in_array($text, ['ثبت و انتشار', 'ثبت تغییرات'], true) && in_array($draft->state, ['review', 'awaiting_save_choice'], true)) return $this->save($draft, 'active', $chatId);
        if ($draft->state === 'awaiting_setting_prompt') {
            return $this->saveSettingPrompt($manager, $draft, $chatId, $text);
        }
        if ($draft->state === 'awaiting_product_code') {
            return $this->receiveProductCode($draft, $chatId, $text);
        }
        if (in_array($draft->state, ['awaiting_prompt', 'awaiting_description'], true)) {
            if (mb_strlen($text) < 10) {
                return $this->response($chatId, 'متن ورودی کمی کوتاه است؛ پرامپت، نام یا توضیحات محصول را کامل‌تر بفرستید.', [], ['status' => 'awaiting_prompt', 'draft_id' => $draft->id]);
            }
            $draft->forceFill([
                'description' => $text,
                'state' => 'processing',
                'processing_started_at' => now(),
                'reviewed_at' => null,
            ])->save();
            $this->rememberMessage($draft, $input['message_id'] ?? null);
            ProcessTelegramProductDraftJob::dispatch($draft->id)->afterCommit();
            return $this->response($chatId, 'اطلاعات در حال آماده‌سازی است؛ چند لحظه صبر کنید.', [
                ['text' => 'لغو فرآیند', 'callback_data' => 'product:cancel'],
            ], [
                'status' => 'processing',
                'draft_id' => $draft->id,
                'delete_message_ids' => $this->messageIds($draft),
                'poll_after_seconds' => 3,
                'reply_markup' => $this->mainMenuMarkup(),
            ]);
        }
        if ($draft->state === 'awaiting_edit') {
            return $this->applyEdit($draft, $chatId, $text);
        }

        return $this->response($chatId, 'لطفاً یکی از دکمه‌های پیام قبلی را انتخاب کنید.', [], ['status' => $draft->state, 'draft_id' => $draft->id]);
    }

    private function beginProductEdit(TelegramProductManager $manager, ?TelegramProductDraft $draft, string $chatId, array $input): array
    {
        if ($draft && $draft->isActive()) {
            $draft->forceFill([
                'state' => 'awaiting_product_code',
                'pending_edit_field' => null,
                'product_id' => null,
                'input_payload' => $this->safePayload($input),
            ])->save();
        } else {
            $draft = TelegramProductDraft::query()->create([
                'id' => (string) Str::uuid(),
                'telegram_product_manager_id' => $manager->id,
                'telegram_id' => $manager->telegram_id,
                'chat_id' => $chatId,
                'state' => 'awaiting_product_code',
                'input_payload' => $this->safePayload($input),
            ]);
        }

        return $this->response($chatId, 'کد محصول را ارسال کنید تا اطلاعات آن برای ویرایش آماده شود.', [], [
            'status' => 'awaiting_product_code',
            'draft_id' => $draft->id,
            'reply_markup' => $this->mainMenuMarkup(),
        ]);
    }

    private function receiveProductCode(TelegramProductDraft $draft, string $chatId, string $code): array
    {
        $code = trim($code);
        if ($code === '') {
            return $this->response($chatId, 'کد محصول خالی است؛ کد محصول را دوباره ارسال کنید.', [], [
                'status' => 'awaiting_product_code',
                'draft_id' => $draft->id,
                'reply_markup' => $this->mainMenuMarkup(),
            ]);
        }

        $product = Product::query()->where('product_code', $code)->first();
        if (! $product) {
            return $this->response($chatId, 'محصولی با این کد پیدا نشد؛ کد محصول را بررسی و دوباره ارسال کنید.', [], [
                'status' => 'awaiting_product_code',
                'draft_id' => $draft->id,
                'reply_markup' => $this->mainMenuMarkup(),
            ]);
        }

        $draft->forceFill([
            'product_id' => $product->id,
            'state' => 'review',
            'ai_result' => [
                'name_fa' => (string) $product->name_fa,
                'name_en' => (string) $product->name_en,
                'description_fa' => (string) $product->description_fa,
                'description_en' => (string) $product->description_en,
                'category' => (string) ($product->category ?: 'عمومی'),
                'category_name' => (string) ($product->category ?: 'عمومی'),
                'tags' => (array) ($product->tags ?? []),
                'product_prompt' => (string) ($product->prompt_template ?? ''),
            ],
            'input_payload' => array_merge((array) $draft->input_payload, ['edit_existing' => true]),
        ])->save();

        return $this->reviewResponse($draft->fresh());
    }

    private function applyEdit(TelegramProductDraft $draft, string $chatId, string $text): array
    {
        if ($text === '') {
            return $this->response($chatId, 'متن اصلاحی خالی است؛ دوباره وارد کنید.');
        }
        $ai = (array) $draft->ai_result;
        $field = (string) $draft->pending_edit_field;
        if ($field === 'metadata') {
            $draft->forceFill([
                'description' => $text,
                'state' => 'processing',
                'pending_edit_field' => null,
                'processing_started_at' => now(),
                'reviewed_at' => null,
            ])->save();
            ProcessTelegramProductDraftJob::dispatch($draft->id)->afterCommit();
            return $this->response($chatId, 'متن ویرایش‌شده دریافت شد؛ نام و توضیحات دوباره آماده می‌شود.', [
                ['text' => 'لغو فرآیند', 'callback_data' => 'product:cancel'],
            ], ['status' => 'processing', 'draft_id' => $draft->id, 'poll_after_seconds' => 3, 'reply_markup' => $this->mainMenuMarkup()]);
        }
        if (! in_array($field, ['name_fa', 'name_en', 'description_fa'], true)) {
            return $this->response($chatId, 'این نوع اصلاح پشتیبانی نمی‌شود.');
        }
        if ($field === 'description_fa') {
            $draft->forceFill(['description' => $text, 'state' => 'processing', 'pending_edit_field' => null, 'processing_started_at' => now(), 'reviewed_at' => null])->save();
            ProcessTelegramProductDraftJob::dispatch($draft->id)->afterCommit();
            return $this->response($chatId, 'توضیح اصلاح شد و اطلاعات دوباره آماده می‌شود.', [
                ['text' => 'دریافت نتیجه', 'callback_data' => 'product:status'],
                ['text' => 'لغو فرآیند', 'callback_data' => 'product:cancel'],
            ], ['status' => 'processing', 'draft_id' => $draft->id, 'poll_after_seconds' => 3]);
        }
        $ai[$field] = $text;
        $draft->forceFill(['ai_result' => $ai, 'state' => 'review', 'pending_edit_field' => null, 'reviewed_at' => null])->save();
        return $this->reviewResponse($draft->fresh());
    }

    private function save(TelegramProductDraft $draft, string $status, string $chatId): array
    {
        if (! in_array($draft->state, ['review', 'awaiting_save_choice'], true)) {
            return $this->response($chatId, 'این فرآیند هنوز برای ذخیره آماده نیست.', [], ['status' => $draft->state, 'draft_id' => $draft->id]);
        }
        $ai = (array) $draft->ai_result;
        if ($draft->product_id && data_get($draft->input_payload, 'edit_existing')) {
            $product = Product::query()->find($draft->product_id);
            if (! $product) return $this->response($chatId, 'محصول موردنظر دیگر پیدا نشد.', [], ['status' => 'failed', 'draft_id' => $draft->id]);
            $product->forceFill([
                'name_fa' => trim((string) ($ai['name_fa'] ?? $product->name_fa)),
                'name_en' => trim((string) ($ai['name_en'] ?? $product->name_en)),
                'description_fa' => trim((string) ($ai['description_fa'] ?? $product->description_fa)),
                'description_en' => trim((string) ($ai['description_en'] ?? $product->description_en)),
                'tags' => array_values((array) ($ai['tags'] ?? $product->tags ?? [])),
            ])->save();
            $draft->forceFill(['state' => 'published', 'completed_at' => now()])->save();
            return $this->savedResponse($draft->fresh(), $product->fresh(), $chatId, 'active');
        }
        $duplicate = $this->duplicateProduct((string) ($ai['name_fa'] ?? ''), (string) ($ai['name_en'] ?? ''));
        if ($duplicate) {
            $draft->forceFill(['state' => 'duplicate', 'reviewed_at' => null, 'ai_result' => array_merge($ai, ['duplicate_product_id' => $duplicate->id])])->save();
            return $this->reviewResponse($draft->fresh());
        }

        $model = AiModel::query()->selectableForProduct()->whereIn('provider', ProviderStatus::enabled() ?: ['__none__'])->first()
            ?: AiModel::query()->where('is_active', true)->first();
        if (! $model) {
            return $this->response($chatId, 'مدل پیش‌فرض فعالی برای محصول پیدا نشد؛ ابتدا تنظیمات مدل‌ها را بررسی کنید.', [], ['status' => 'failed', 'draft_id' => $draft->id]);
        }
        $category = Category::query()->find($ai['category_id'] ?? null) ?: Category::query()->active()->first();
        $slugBase = Str::slug((string) ($ai['name_en'] ?? 'vatan-product')) ?: 'vatan-product';
        $slug = $slugBase;
        $counter = 1;
        while (Product::query()->where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . (++$counter);
        }

        $paths = array_values(array_filter((array) $draft->image_paths));
        $isFaceOriented = (bool) ($ai['is_face_oriented'] ?? false);
        $subjectType = in_array(($ai['subject_type'] ?? null), ['generic', 'face', 'body', 'product', 'scene'], true)
            ? $ai['subject_type']
            : ($isFaceOriented ? 'face' : 'product');
        $product = DB::transaction(function () use ($draft, $status, $ai, $model, $category, $slug, $paths, $isFaceOriented, $subjectType): Product {
            $product = Product::query()->create([
                'name_fa' => trim((string) $ai['name_fa']),
                'name_en' => trim((string) $ai['name_en']),
                'slug' => $slug,
                'product_code' => Product::generateUniqueProductCode(),
                'description_fa' => trim((string) $ai['description_fa']),
                'description_en' => trim((string) $ai['description_en']),
                'category_id' => $category?->id,
                'category' => $category?->name ?: 'عمومی',
                'status' => $status,
                'tags' => array_values((array) ($ai['tags'] ?? [])),
                'thumbnail' => $paths[0] ?? 'products/thumbnails/default_placeholder.jpg',
                'cover' => $paths[0] ?? null,
                'sample_outputs' => array_slice($paths, 1),
                'media_type' => 'photo',
                'primary_model' => $model->openrouter_model_id,
                'ai_provider' => $model->provider,
                'fallback_models' => [],
                'fallback_model_providers' => [],
                'model_configuration' => [],
                'prompt_template' => trim((string) ($ai['product_prompt'] ?? '')) ?: 'Create a high quality image based on {prompt}. Product context: ' . (string) $draft->description,
                'negative_prompt' => trim((string) ($ai['negative_prompt'] ?? '')),
                'input_schema' => [],
                'subject_type' => $subjectType,
                'identity_preservation' => $isFaceOriented,
                'identity_instructions' => $isFaceOriented ? ProductPromptBuilder::defaultIdentityInstructions() : null,
                'identity_instructions_fa' => $isFaceOriented ? ProductPromptBuilder::defaultIdentityInstructionsFa() : null,
                'min_reference_images' => 1,
                'max_reference_images' => 3,
                'new_display_order' => 1,
                'new_watermark_corner_precise' => 'tr',
                'new_watermark_opacity' => 70,
                'new_watermark_size' => 30,
                'new_watermark_type' => 'logo',
                'new_watermark_text_color' => '#FFFFFF',
                'new_min_credit_required' => 0,
                'new_show_free_badge' => false,
                'allowed_aspect_ratios' => Product::supportedAspectRatios(),
                'allowed_resolutions' => Product::DEFAULT_OUTPUT_RESOLUTIONS,
                'aspect_ratio' => '3:4',
                'resolution' => '720',
                'output_type' => 'image',
                'output_format' => 'jpg',
                'output_count' => 1,
                'delivery_method' => 'instant',
                'estimated_time' => 30,
                'watermark_enabled' => false,
                'pricing_model' => 'per_credit',
                'credit_cost' => 12,
                'price_tier' => 'standard',
                'platform' => 'both',
                'display_mode' => 'card',
                'card_shape' => 'portrait',
                'gallery_layout' => 'grid',
                'accent_color' => '#a07af5',
                'explore_tiles' => ['1x1', '2x2', '1x2', '2x1'],
                'is_new' => true,
            ]);
            if ($draft->manager?->admin_id) {
                $product->forceFill(['created_by' => $draft->manager->admin_id, 'updated_by' => $draft->manager->admin_id])->save();
            }
            $draft->forceFill([
                'product_id' => $product->id,
                'state' => $status === 'active' ? 'published' : 'draft_saved',
                'completed_at' => now(),
            ])->save();
            if (Schema::hasTable('telegram_product_registrations')) {
                TelegramProductRegistration::query()->updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'telegram_product_manager_id' => $draft->telegram_product_manager_id,
                        'draft_id' => $draft->id,
                        'telegram_id' => $draft->telegram_id,
                        'input_prompt' => $draft->description,
                        'ai_result' => $ai,
                        'status' => $status,
                    ],
                );
            }
            return $product;
        });

        return $this->savedResponse($draft->fresh(), $product, $chatId, $status);
    }

    private function reviewResponse(TelegramProductDraft $draft): array
    {
        $ai = (array) $draft->ai_result;
        $duplicate = $draft->state === 'duplicate';
        $tags = collect((array) ($ai['tags'] ?? []))
            ->map(fn ($tag) => '#' . ltrim(str_replace([' ', '‌'], '_', trim((string) $tag)), '#'))
            ->filter(fn ($tag) => $tag !== '#')
            ->implode(' ');
        $categories = collect((array) ($ai['categories'] ?? []))
            ->merge(array_filter([(string) ($ai['category_name'] ?? ''), (string) ($ai['subcategory'] ?? '')]))
            ->map(fn ($category) => trim((string) $category))
            ->filter()
            ->unique()
            ->implode('، ');
        $text = "نام محصول:\n" . trim((string) ($ai['name_fa'] ?? ''))
            . "\nنام انگلیسی:\n" . trim((string) ($ai['name_en'] ?? ''))
            . "\n\nتوضیحات فارسی:\n" . trim((string) ($ai['description_fa'] ?? ''))
            . "\n\nهشتگ‌ها:\n" . ($tags ?: '—')
            . "\n\nدسته‌بندی‌ها:\n" . ($categories ?: 'عمومی')
            . "\n\nچهره‌محور بودن پرامپت:\n" . (! empty($ai['is_face_oriented']) ? 'بله' : 'خیر')
            . "\n\nپرامپت نهایی:\n" . trim((string) ($ai['product_prompt'] ?? ''))
            . "\n\nتوضیحات تکمیلی:\n" . (trim((string) ($ai['other_details'] ?? '')) ?: '—');
        if ($duplicate) {
            $text .= "\n\n⚠️ محصولی با عنوان مشابه قبلاً ثبت شده است؛ قبل از ثبت آن را اصلاح کنید.";
        }
        return $this->response($draft->chat_id, $text, [
            ['text' => 'تأیید', 'callback_data' => 'product:confirm'],
            ['text' => 'کپی برای ویرایش', 'callback_data' => 'product:edit:metadata'],
            ['text' => 'لغو فرآیند', 'callback_data' => 'product:cancel'],
        ], ['status' => $draft->state, 'ready' => true, 'draft_id' => $draft->id, 'delete_message_ids' => $this->messageIds($draft), 'keyboard' => 'review', 'photo_url' => $this->draftPhotoUrl($draft)]);
    }

    private function draftPhotoUrl(TelegramProductDraft $draft): ?string
    {
        $path = collect((array) $draft->image_paths)->filter()->first();
        return $path ? Storage::disk('public')->url((string) $path) : null;
    }

    private function statusResponse(TelegramProductDraft $draft, string $chatId): array
    {
        $draft->refresh();

        if ($draft->state === 'processing') {
            return $this->response($chatId, 'اطلاعات هنوز در حال آماده‌سازی است؛ چند لحظه بعد دوباره بررسی کنید.', [
                ['text' => 'دریافت نتیجه', 'callback_data' => 'product:status'],
                ['text' => 'لغو فرآیند', 'callback_data' => 'product:cancel'],
            ], ['status' => 'processing', 'draft_id' => $draft->id, 'poll_after_seconds' => 3]);
        }

        if (in_array($draft->state, ['review', 'duplicate'], true)) {
            return $this->reviewResponse($draft);
        }

        if ($draft->state === 'failed') {
            return $this->response($chatId, 'آماده‌سازی اطلاعات با خطا روبه‌رو شد؛ لطفاً توضیح محصول را دوباره ارسال کنید.', [
                ['text' => 'لغو فرآیند', 'callback_data' => 'product:cancel'],
            ], ['status' => 'failed', 'draft_id' => $draft->id]);
        }

        return $this->response($chatId, 'این فرآیند دیگر در وضعیت بررسی نیست.', [], ['status' => $draft->state, 'draft_id' => $draft->id]);
    }

    private function confirmMetadata(TelegramProductDraft $draft, string $chatId): array
    {
        if (! in_array($draft->state, ['review', 'duplicate'], true)) {
            return $this->response($chatId, 'ابتدا باید نتیجه‌ی آماده‌شده را بررسی کنید.', [], ['status' => $draft->state, 'draft_id' => $draft->id]);
        }
        if ($draft->state === 'duplicate') return $this->reviewResponse($draft->fresh());
        $draft->forceFill(['state' => 'awaiting_save_choice', 'reviewed_at' => now()])->save();
        $isEdit = (bool) data_get($draft->input_payload, 'edit_existing');
        $text = $isEdit ? 'اطلاعات اصلاح‌شده تأیید شد. نوع ذخیره‌ی تغییرات را انتخاب کنید.' : 'نام و توضیحات تأیید شد. حالا نوع ثبت محصول را انتخاب کنید.';
        return $this->response($chatId, $text, [
            ['text' => $isEdit ? 'ثبت تغییرات' : 'ذخیره در پیش‌نویس', 'callback_data' => $isEdit ? 'product:publish' : 'product:save_draft'],
            ...($isEdit ? [] : [['text' => 'ثبت و انتشار', 'callback_data' => 'product:publish']]),
            ['text' => 'کپی برای ویرایش', 'callback_data' => 'product:edit:metadata'],
            ['text' => 'لغو فرآیند', 'callback_data' => 'product:cancel'],
        ], ['status' => 'awaiting_save_choice', 'draft_id' => $draft->id, 'keyboard' => 'review']);
    }

    private function settingsMenu(TelegramProductManager $manager, string $chatId): array
    {
        return $this->response($chatId, "تنظیمات بات ثبت محصول\n\nاز این بخش می‌توانید پرامپت‌های تولید اطلاعات محصول را مدیریت کنید.", [
            ['text' => 'تنظیمات پرامپت مادر محصول', 'callback_data' => 'product:settings:metadata'],
            ['text' => 'تنظیمات پرامپت اصلاح پرامپت محصول', 'callback_data' => 'product:settings:optimizer'],
        ], ['status' => 'settings']);
    }

    private function settingsAction(TelegramProductManager $manager, string $chatId, string $key): array
    {
        if (! $manager->can('manage_prompts')) return $this->response($chatId, 'دسترسی مدیریت پرامپت‌ها برای حساب شما فعال نیست.', [], ['status' => 'forbidden']);
        $settingKey = $key === 'metadata' ? 'metadata_prompt' : ($key === 'optimizer' ? 'prompt_optimizer' : null);
        if (! $settingKey) return $this->settingsMenu($manager, $chatId);
        $draft = TelegramProductDraft::query()->create([
            'id' => (string) Str::uuid(),
            'telegram_product_manager_id' => $manager->id,
            'telegram_id' => $manager->telegram_id,
            'chat_id' => $chatId,
            'state' => 'awaiting_setting_prompt',
            'pending_edit_field' => $settingKey,
        ]);
        $current = TelegramProductSetting::value($settingKey, '');
        return $this->response($chatId, "پرامپت فعلی:\n\n{$current}\n\n👇 متن جدید را در یک پیام ارسال کنید:", [
            ['text' => 'لغو اصلاح', 'callback_data' => 'product:cancel'],
        ], ['status' => 'awaiting_setting_prompt', 'draft_id' => $draft->id]);
    }

    private function saveSettingPrompt(TelegramProductManager $manager, TelegramProductDraft $draft, string $chatId, string $text): array
    {
        if (! $manager->can('manage_prompts')) return $this->response($chatId, 'دسترسی مدیریت پرامپت‌ها برای حساب شما فعال نیست.', [], ['status' => 'forbidden']);
        if (mb_strlen($text) < 20) return $this->response($chatId, 'پرامپت خیلی کوتاه است؛ متن کامل‌تری ارسال کنید.', [], ['status' => 'awaiting_setting_prompt', 'draft_id' => $draft->id]);
        TelegramProductSetting::put((string) $draft->pending_edit_field, $text);
        $draft->forceFill(['state' => 'cancelled', 'cancelled_at' => now(), 'pending_edit_field' => null])->save();
        return $this->response($chatId, 'پرامپت با موفقیت ذخیره شد.', [], ['status' => 'settings_saved', 'reply_markup' => $this->mainMenuMarkup()]);
    }

    private function education(string $chatId): array
    {
        return $this->response($chatId, "آموزش ثبت محصول\n\n۱) روی «ثبت محصول جدید» بزنید و عکس اصلی را ارسال کنید.\n۲) بعد از دریافت عکس، پرامپت ساخت محصول را همراه با نام و توضیحات در یک پیام بفرستید.\n۳) نام و توضیحات پیشنهادی را بررسی کنید و «تأیید» یا «کپی برای ویرایش» را بزنید.\n۴) نوع ثبت را انتخاب کنید: پیش‌نویس یا انتشار در سایت.\n۵) در پایان کد محصول و لینک نمایش آن برای شما ارسال می‌شود.\n\nبرای ویرایش محصول قبلی، «ویرایش محصول» را بزنید و کد محصول را ارسال کنید.", [], ['status' => 'education', 'reply_markup' => $this->mainMenuMarkup()]);
    }

    private function savedResponse(TelegramProductDraft $draft, Product $product, string $chatId, string $status): array
    {
        $this->cleanupMessages($draft);
        $ai = (array) $draft->ai_result;
        $buttons = [];
        if ($status === 'active') {
            $buttons[] = ['text' => 'مشاهده محصول در سایت', 'url' => route('app.product', ['product' => $product->route_slug])];
        }
        $buttons[] = ['text' => 'ویرایش در داشبورد', 'url' => route('admin.products.create', ['product' => $product->id])];
        $text = ($status === 'active' ? '✅ محصول با موفقیت ثبت و منتشر شد.' : '✅ محصول با موفقیت در پیش‌نویس ذخیره شد.')
            . "\n\nکد محصول: {$product->product_code}"
            . "\nنام: {$product->name_fa}"
            . "\nتوضیحات: {$product->description_fa}"
            . "\nدسته‌بندی: " . ($ai['category_name'] ?? $product->category)
            . "\nبرچسب‌ها: " . implode('، ', (array) ($ai['tags'] ?? $product->tags ?? []));
        return $this->response($chatId, $text, $buttons, [
            'status' => $product->status,
            'draft_id' => $draft->id,
            'product_id' => $product->id,
            'delete_message_ids' => $this->messageIds($draft),
            'photo_url' => filled($product->thumbnail) ? Storage::disk('public')->url($product->thumbnail) : null,
            'final_product' => true,
        ]);
    }

    private function editPrompt(TelegramProductDraft $draft, string $chatId, string $field): array
    {
        $labels = ['metadata' => 'نام و توضیحات محصول', 'name_fa' => 'نام فارسی', 'name_en' => 'نام انگلیسی', 'description_fa' => 'توضیح فارسی'];
        if (! isset($labels[$field]) || ! in_array($draft->state, ['review', 'duplicate', 'awaiting_save_choice'], true)) {
            return $this->response($chatId, 'این بخش فعلاً قابل اصلاح نیست.');
        }
        $draft->forceFill(['state' => 'awaiting_edit', 'pending_edit_field' => $field])->save();
        if ($field === 'metadata') {
            $ai = (array) $draft->ai_result;
            $prompt = "👇 متن زیر را کپی کن، هر بخش را خواستی کم‌وزیاد یا ویرایش کن و در یک پیام بفرست:\n\nنام محصول: {$ai['name_fa']}\n\nتوضیحات محصول: {$ai['description_fa']}";
        } else {
            $prompt = "متن جدید «{$labels[$field]}» را ارسال کنید.";
        }
        return $this->response($chatId, $prompt, [
            ['text' => 'لغو اصلاح', 'callback_data' => 'product:cancel_edit'],
        ], ['status' => 'awaiting_edit', 'draft_id' => $draft->id]);
    }

    private function setStateResponse(TelegramProductDraft $draft, string $state, string $chatId, string $message): array
    {
        $draft->forceFill(['state' => $state, 'pending_edit_field' => null])->save();
        return $this->response($chatId, $message, [['text' => 'لغو فرآیند', 'callback_data' => 'product:cancel']], ['status' => $state, 'draft_id' => $draft->id]);
    }

    private function cancel(TelegramProductDraft $draft, string $chatId): array
    {
        $messageIds = $this->messageIds($draft);
        $draft->forceFill(['state' => 'cancelled', 'cancelled_at' => now(), 'pending_edit_field' => null])->save();
        $this->cleanupMessages($draft);
        return $this->response($chatId, 'فرآیند ثبت محصول لغو شد؛ هیچ محصولی حذف نشد.', [], ['status' => 'cancelled', 'draft_id' => $draft->id, 'delete_message_ids' => $messageIds, 'reply_markup' => $this->mainMenuMarkup()]);
    }

    private function cancelEdit(TelegramProductDraft $draft, string $chatId): array
    {
        $state = $draft->ai_result ? 'review' : 'awaiting_description';
        $draft->forceFill(['state' => $state, 'pending_edit_field' => null])->save();
        return $state === 'review'
            ? $this->reviewResponse($draft->fresh())
            : $this->response($chatId, 'اصلاح لغو شد.');
    }

    private function activeDraft(TelegramProductManager $manager, string $chatId): ?TelegramProductDraft
    {
        return TelegramProductDraft::query()->where('telegram_product_manager_id', $manager->id)->where('chat_id', $chatId)->whereIn('state', TelegramProductDraft::ACTIVE_STATES)->latest()->first();
    }

    private function duplicateProduct(string $nameFa, string $nameEn): ?Product
    {
        $fa = $this->normalizeTitle($nameFa);
        $en = $this->normalizeTitle($nameEn);
        if ($fa === '' && $en === '') return null;
        return Product::query()->whereIn('status', ['draft', 'active', 'inactive'])->get()->first(function (Product $product) use ($fa, $en): bool {
            $existingFa = $this->normalizeTitle((string) $product->name_fa);
            $existingEn = $this->normalizeTitle((string) $product->name_en);
            return ($fa !== '' && $fa === $existingFa) || ($en !== '' && $en === $existingEn);
        });
    }

    private function matchCategory(string $value, $categories): ?Category
    {
        $needle = $this->normalizeTitle($value);
        return $categories->first(function (Category $category) use ($needle): bool {
            return $needle !== '' && in_array($needle, [
                $this->normalizeTitle($category->name_fa),
                $this->normalizeTitle($category->name),
                $this->normalizeTitle($category->name_en),
            ], true);
        });
    }

    private function normalizeTitle(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace(['ي', 'ى', 'ك', 'ۀ', 'ة'], ['ی', 'ی', 'ک', 'ه', 'ه'], mb_strtolower($value))) ?: '');
    }

    private function eventType(array $input, ?UploadedFile $image): string
    {
        if ($image || ($input['event'] ?? null) === 'photo') return 'photo';
        if (($input['event'] ?? null) === 'start' || str_starts_with(trim((string) ($input['text'] ?? '')), '/start')) return 'start';
        if (! empty($input['callback_data'])) return 'callback';
        return 'message';
    }

    private function response(string $chatId, string $text, array $buttons = [], array $extra = []): array
    {
        $buttons = array_values($buttons);
        $response = [
            'ok' => true,
            'action' => 'send_message',
            'chat_id' => $chatId,
            'text' => $text,
            'buttons' => $buttons,
        ];
        if ($buttons !== [] && ! array_key_exists('reply_markup', $extra)) {
            $response['reply_markup'] = $this->markupForButtons($buttons);
        }

        return array_merge($response, $extra);
    }

    private function markupForButtons(array $buttons): array
    {
        $isInline = collect($buttons)->contains(fn (array $button): bool => filled($button['url'] ?? null));
        if ($isInline) {
            return ['inline_keyboard' => [$buttons]];
        }

        $keyboard = [];
        $row = [];
        foreach ($buttons as $button) {
            $row[] = ['text' => (string) ($button['text'] ?? '')];
            if (count($row) === 2 || ($button['text'] ?? '') === 'لغو فرآیند' || ($button['text'] ?? '') === 'لغو اصلاح') {
                $keyboard[] = $row;
                $row = [];
            }
        }
        if ($row !== []) {
            $keyboard[] = $row;
        }

        return [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'is_persistent' => true,
            'input_field_placeholder' => 'یک گزینه را انتخاب کنید',
        ];
    }

    private function mainMenuMarkup(): array
    {
        return [
            'keyboard' => [
                [['text' => 'ثبت محصول جدید'], ['text' => 'ویرایش محصول']],
                [['text' => 'تنظیمات'], ['text' => 'آموزش']],
                [['text' => 'لغو فرآیند']],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
            'input_field_placeholder' => 'یک گزینه را انتخاب کنید',
        ];
    }

    private function rememberMessage(TelegramProductDraft $draft, mixed $messageId): void
    {
        if ($messageId === null || $messageId === '') return;
        $ids = array_values(array_unique(array_merge((array) $draft->message_ids, [(string) $messageId])));
        $draft->forceFill(['message_ids' => $ids, 'last_message_id' => (string) $messageId])->save();
    }

    private function messageIds(TelegramProductDraft $draft): array
    {
        return array_values(array_filter(array_map('strval', (array) $draft->message_ids)));
    }

    private function cleanupMessages(TelegramProductDraft $draft): void
    {
        $token = trim((string) config('services.telegram_product.bot_token'));
        if ($token === '') return;
        foreach ($this->messageIds($draft) as $messageId) {
            try {
                Http::timeout(5)->post("https://api.telegram.org/bot{$token}/deleteMessage", [
                    'chat_id' => $draft->chat_id,
                    'message_id' => (int) $messageId,
                ]);
            } catch (\Throwable) {
                // پاک‌سازی کمکی است؛ خطای آن نباید ثبت محصول را متوقف کند.
            }
        }
    }

    private function safePayload(array $input): array
    {
        unset($input['image'], $input['file'], $input['raw']);
        return $input;
    }
}
