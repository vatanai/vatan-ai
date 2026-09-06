<?php

namespace App\Services;

use App\Jobs\ProcessTelegramProductDraftJob;
use App\Models\AiModel;
use App\Models\Category;
use App\Models\Product;
use App\Models\TelegramProductDraft;
use App\Models\TelegramProductEvent;
use App\Models\TelegramProductManager;
use App\Support\ProviderStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

        return $this->response($chatId, 'ثبت محصول جدید شروع شد. تصویر اصلی محصول را ارسال کنید.', [
            ['text' => 'لغو فرآیند', 'callback_data' => 'product:cancel'],
        ], ['status' => 'awaiting_image', 'draft_id' => $draft->id, 'delete_message_ids' => $this->messageIds($draft)]);
    }

    private function photo(TelegramProductManager $manager, ?TelegramProductDraft $draft, string $chatId, array $input, ?UploadedFile $image): array
    {
        if (! $draft || $draft->state !== 'awaiting_image') {
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

        $paths[] = $this->images->store($image, 'products/telegram');
        $draft->forceFill(['image_paths' => array_values($paths), 'state' => 'awaiting_description'])->save();
        $this->rememberMessage($draft, $input['message_id'] ?? null);

        return $this->response($chatId, 'تصویر ثبت شد. اگر تصویر دیگری دارید، اضافه کنید؛ سپس توضیح محصول را وارد کنید.', [
            ['text' => 'افزودن تصویر دیگر', 'callback_data' => 'product:add_image'],
            ['text' => 'واردکردن توضیح', 'callback_data' => 'product:describe'],
            ['text' => 'لغو فرآیند', 'callback_data' => 'product:cancel'],
        ], ['status' => 'awaiting_description', 'draft_id' => $draft->id, 'delete_message_ids' => $this->messageIds($draft)]);
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
        if (! $draft) {
            return $this->response($chatId, 'فرآیند فعالی وجود ندارد. ابتدا ثبت محصول جدید را شروع کنید.');
        }

        return match ($action[1] ?? '') {
            'add_image' => $this->setStateResponse($draft, 'awaiting_image', $chatId, 'تصویر بعدی را ارسال کنید.'),
            'describe' => $this->setStateResponse($draft, 'awaiting_description', $chatId, 'توضیح محصول را در یک پیام بفرستید.'),
            'status' => $this->statusResponse($draft, $chatId),
            'cancel' => $this->cancel($draft, $chatId),
            'cancel_edit' => $this->cancelEdit($draft, $chatId),
            'edit' => $this->editPrompt($draft, $chatId, $action[2] ?? ''),
            'save_draft' => $this->save($draft, 'draft', $chatId),
            'publish' => $this->save($draft, 'active', $chatId),
            default => $this->response($chatId, 'این دکمه دیگر معتبر نیست. لطفاً وضعیت فعلی را دوباره بررسی کنید.'),
        };
    }

    private function text(TelegramProductManager $manager, ?TelegramProductDraft $draft, string $chatId, array $input): array
    {
        $text = trim((string) ($input['text'] ?? ''));
        if (! $draft) {
            return $this->response($chatId, 'برای ثبت محصول، از دکمه‌ی شروع استفاده کنید.', [
                ['text' => 'ثبت محصول جدید', 'callback_data' => 'product:start'],
            ], ['status' => 'no_draft']);
        }
        if ($draft->state === 'awaiting_description') {
            if (mb_strlen($text) < 10) {
                return $this->response($chatId, 'توضیح محصول کمی کوتاه است؛ حداقل ۱۰ کاراکتر وارد کنید.', [], ['status' => 'awaiting_description', 'draft_id' => $draft->id]);
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
                ['text' => 'دریافت نتیجه', 'callback_data' => 'product:status'],
                ['text' => 'لغو فرآیند', 'callback_data' => 'product:cancel'],
            ], [
                'status' => 'processing',
                'draft_id' => $draft->id,
                'delete_message_ids' => $this->messageIds($draft),
                'poll_after_seconds' => 3,
            ]);
        }
        if ($draft->state === 'awaiting_edit') {
            return $this->applyEdit($draft, $chatId, $text);
        }

        return $this->response($chatId, 'لطفاً یکی از دکمه‌های پیام قبلی را انتخاب کنید.', [], ['status' => $draft->state, 'draft_id' => $draft->id]);
    }

    private function applyEdit(TelegramProductDraft $draft, string $chatId, string $text): array
    {
        if ($text === '') {
            return $this->response($chatId, 'متن اصلاحی خالی است؛ دوباره وارد کنید.');
        }
        $ai = (array) $draft->ai_result;
        $field = (string) $draft->pending_edit_field;
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
        if (! in_array($draft->state, ['review'], true)) {
            return $this->response($chatId, 'این فرآیند هنوز برای ذخیره آماده نیست.', [], ['status' => $draft->state, 'draft_id' => $draft->id]);
        }
        $ai = (array) $draft->ai_result;
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
        $product = DB::transaction(function () use ($draft, $status, $ai, $model, $category, $slug, $paths): Product {
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
                'prompt_template' => 'Create a high quality image based on {prompt}. Product context: ' . (string) $draft->description,
                'input_schema' => [],
                'identity_preservation' => false,
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
            return $product;
        });

        return $this->savedResponse($draft->fresh(), $product, $chatId, $status);
    }

    private function reviewResponse(TelegramProductDraft $draft): array
    {
        $ai = (array) $draft->ai_result;
        $duplicate = $draft->state === 'duplicate';
        $text = "خلاصه محصول آماده است:\n\nنام فارسی: {$ai['name_fa']}\nنام انگلیسی: {$ai['name_en']}\nدسته‌بندی: " . ($ai['category_name'] ?? $ai['category']) . "\nتوضیح: {$ai['description_fa']}";
        if ($duplicate) {
            $text .= "\n\nهشدار: محصولی با عنوان مشابه قبلاً ثبت شده است؛ ذخیره متوقف شد.";
        }
        return $this->response($draft->chat_id, $text, $duplicate ? [
            ['text' => 'اصلاح نام فارسی', 'callback_data' => 'product:edit:name_fa'],
            ['text' => 'اصلاح توضیح', 'callback_data' => 'product:edit:description_fa'],
            ['text' => 'لغو فرآیند', 'callback_data' => 'product:cancel'],
        ] : [
            ['text' => 'اصلاح نام فارسی', 'callback_data' => 'product:edit:name_fa'],
            ['text' => 'اصلاح نام انگلیسی', 'callback_data' => 'product:edit:name_en'],
            ['text' => 'اصلاح توضیح', 'callback_data' => 'product:edit:description_fa'],
            ['text' => 'ذخیره در پیش‌نویس', 'callback_data' => 'product:save_draft'],
            ['text' => 'انتشار در وب‌سایت', 'callback_data' => 'product:publish'],
            ['text' => 'لغو فرآیند', 'callback_data' => 'product:cancel'],
        ], ['status' => $draft->state, 'ready' => true, 'draft_id' => $draft->id, 'delete_message_ids' => $this->messageIds($draft)]);
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

    private function savedResponse(TelegramProductDraft $draft, Product $product, string $chatId, string $status): array
    {
        $buttons = [['text' => 'ویرایش در داشبورد', 'url' => route('admin.products.create', ['product' => $product->id])]];
        if ($status === 'active') {
            $buttons[] = ['text' => 'مشاهده محصول در سایت', 'url' => route('app.product', ['product' => $product->route_slug])];
        }
        return $this->response($chatId, $status === 'active' ? 'محصول با موفقیت منتشر شد.' : 'محصول در وضعیت پیش‌نویس ذخیره شد.', $buttons, [
            'status' => $product->status,
            'draft_id' => $draft->id,
            'product_id' => $product->id,
            'delete_message_ids' => $this->messageIds($draft),
        ]);
    }

    private function editPrompt(TelegramProductDraft $draft, string $chatId, string $field): array
    {
        $labels = ['name_fa' => 'نام فارسی', 'name_en' => 'نام انگلیسی', 'description_fa' => 'توضیح فارسی'];
        if (! isset($labels[$field]) || ! in_array($draft->state, ['review', 'duplicate'], true)) {
            return $this->response($chatId, 'این بخش فعلاً قابل اصلاح نیست.');
        }
        $draft->forceFill(['state' => 'awaiting_edit', 'pending_edit_field' => $field])->save();
        return $this->response($chatId, "متن جدید «{$labels[$field]}» را ارسال کنید.", [
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
        $draft->forceFill(['state' => 'cancelled', 'cancelled_at' => now(), 'pending_edit_field' => null])->save();
        return $this->response($chatId, 'فرآیند ثبت محصول لغو شد؛ هیچ محصولی حذف نشد.', [], ['status' => 'cancelled', 'draft_id' => $draft->id, 'delete_message_ids' => $this->messageIds($draft)]);
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
        return TelegramProductDraft::query()->where('telegram_product_manager_id', $manager->id)->whereIn('state', TelegramProductDraft::ACTIVE_STATES)->latest()->first();
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
        return array_merge(['ok' => true, 'action' => 'send_message', 'chat_id' => $chatId, 'text' => $text, 'buttons' => array_values($buttons)], $extra);
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

    private function safePayload(array $input): array
    {
        unset($input['image'], $input['file'], $input['raw']);
        return $input;
    }
}
