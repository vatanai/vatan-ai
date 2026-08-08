<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductMetricEvent;
use App\Models\GeneratedImage;
use App\Models\UserUpload;
use App\Models\Order;
use App\Models\Discount;
use App\Models\ReferralSetting;
use App\Services\AiProviderRouter;
use App\Services\ProductBuildSchema;
use App\Services\ProductPromptBuilder;
use App\Services\SmsEventService;
use App\Http\Requests\GenerateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ProductGenerateController extends Controller
{
    public function __construct(protected AiProviderRouter $openRouter)
    {
    }

    public function create(Request $request, ProductBuildSchema $schema)
    {
        $slug = $request->query('product');
        // هر دو لینک قدیمی و جدید را پشتیبانی می‌کنیم:
        // ?product=slug و ?product=123456-slug باید همان محصول را باز کنند.
        // اگر محصول مشخص شده باشد، هرگز به‌صورت بی‌صدا محصول دیگری جایگزین نشود.
        if ($slug !== null && $slug !== '') {
            $product = (new Product())->resolveRouteBinding($slug, 'route_slug');
            abort_unless($product && $product->status === 'active', 404);
        } else {
            // لینک عمومی «بساز» همچنان یک محصول فعال پیش‌فرض برای شروع دارد.
            $product = Product::where('status', 'active')->latest()->first();
        }

        return view('app.create', [
            'product' => $product,
            'buildProduct' => $product ? $schema->pageData($product) : null,
        ]);
    }

    public function build(Product $product)
    {
        abort_unless($product->status === 'active', 404);

        // مسیر قدیمی را به مسیر اصلی منتقل می‌کنیم تا تمام لینک‌ها یک تجربه و
        // یک فرم ساخت داشته باشند و مشخصات محصول در صفحه‌ی «بساز» از بین نرود.
        return redirect()->route('app.create', ['product' => $product->route_slug]);
    }

    /**
     * پیش‌نمایش ایزوله رابط کاربری «بساز»؛ تا پیش از تأیید نهایی به محصول یا
     * مسیر عملیاتی متصل نمی‌شود و تمام حالت‌های شِمای ورودی را نمایش می‌دهد.
     */
    public function createPreview()
    {
        $previewProduct = $this->previewProductData();

        return view('app.create-preview', compact('previewProduct'));
    }

    private function previewProductData(): array
    {
        return [
            'name' => 'پرتره سینمایی فوق‌واقعی',
            'description' => 'چهره شما با حفظ دقیق هویت، نورپردازی سینمایی و جزئیات طبیعی بازآفرینی می‌شود.',
            'cover' => asset('assets/img/moody-portrait-of-a-young-man-with-a-black-horse-on-a-ranch-ai-photo-editing-prompt.avif'),
            'cost' => 18,
            'estimated_time' => 'حدود ۴۵ ثانیه',
            'output_count' => 4,
            'fields' => $this->previewInputSchema(),
        ];
    }

    private function previewInputSchema(): array
    {
        return [
            ['id' => 'guide', 'type' => 'info', 'label' => 'برای بیشترین شباهت، یک عکس واضح و روبه‌رو با نور طبیعی انتخاب کنید.'],
            ['id' => 'identity_section', 'type' => 'section', 'label' => 'تصاویر هویتی', 'help' => 'تصویری انتخاب کنید که جزئیات صورت در آن کاملاً مشخص باشد.'],
            ['id' => 'portrait', 'type' => 'image_upload', 'label' => 'تصویر اصلی چهره', 'help' => 'JPG، PNG یا WebP · حداکثر ۱۰ مگابایت', 'required' => true],
            ['id' => 'references', 'type' => 'multi_image', 'label' => 'تصاویر مرجع بیشتر', 'help' => 'اختیاری · تا ۳ زاویه دیگر برای حفظ بهتر هویت'],
            ['id' => 'identity_divider', 'type' => 'divider', 'label' => ''],
            ['id' => 'creative_section', 'type' => 'section', 'label' => 'جزئیات خلاقانه', 'help' => 'این موارد ظاهر و فضای خروجی را شخصی‌سازی می‌کنند.'],
            ['id' => 'scene', 'type' => 'textarea', 'label' => 'فضایی که در ذهن دارید', 'placeholder' => 'مثلاً: یک خیابان خیس در شب با نورهای نئونی...'],
            ['id' => 'custom_prompt', 'type' => 'prompt', 'label' => 'دستور اختصاصی شما', 'placeholder' => 'جزئیات خاصی که دوست دارید در خروجی دیده شود...'],
            ['id' => 'title', 'type' => 'text', 'label' => 'متن کوتاه روی تصویر', 'placeholder' => 'اختیاری'],
            ['id' => 'age', 'type' => 'number', 'label' => 'سن ظاهری', 'min' => 18, 'max' => 80, 'value' => 30],
            ['id' => 'mood', 'type' => 'radio', 'label' => 'حس‌وحال چهره', 'value' => 'confident', 'options' => [['value'=>'confident','label'=>'بااعتمادبه‌نفس'],['value'=>'calm','label'=>'آرام'],['value'=>'serious','label'=>'جدی'],['value'=>'smile','label'=>'لبخند ملایم']]],
            ['id' => 'camera', 'type' => 'select', 'label' => 'نوع قاب دوربین', 'value' => 'portrait', 'options' => [['value'=>'close','label'=>'نمای نزدیک'],['value'=>'portrait','label'=>'پرتره نیم‌تنه'],['value'=>'full','label'=>'تمام‌قد']]],
            ['id' => 'details', 'type' => 'multi_select', 'label' => 'جزئیات تکمیلی', 'options' => [['value'=>'rain','label'=>'باران'],['value'=>'fog','label'=>'مه'],['value'=>'grain','label'=>'گرین فیلم'],['value'=>'bokeh','label'=>'بوکه پس‌زمینه']]],
            ['id' => 'lighting', 'type' => 'button_group', 'label' => 'نورپردازی', 'value' => 'cinematic', 'options' => [['value'=>'soft','label'=>'نرم'],['value'=>'cinematic','label'=>'سینمایی'],['value'=>'studio','label'=>'استودیویی']]],
            ['id' => 'identity', 'type' => 'strength', 'label' => 'میزان حفظ شباهت', 'value' => 90, 'min' => 50, 'max' => 100, 'unit' => '٪'],
            ['id' => 'cinematic_depth', 'type' => 'slider', 'label' => 'عمق سینمایی', 'value' => 65, 'min' => 0, 'max' => 100, 'unit' => '٪'],
            ['id' => 'background', 'type' => 'color', 'label' => 'رنگ غالب پس‌زمینه', 'value' => '#18221f'],
            ['id' => 'preserve_body', 'type' => 'switch', 'label' => 'فرم بدن نیز حفظ شود', 'value' => true],
            ['id' => 'confirm', 'type' => 'checkbox', 'label' => 'حق استفاده از تصاویر بارگذاری‌شده را دارم', 'required' => true],
            ['id' => 'style', 'type' => 'style_preset', 'label' => 'استایل خروجی', 'value' => 'cinematic', 'options' => [
                ['value'=>'cinematic','label'=>'سینمایی','image'=>asset('assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg')],
                ['value'=>'editorial','label'=>'ادیتوریال','image'=>asset('assets/img/elegant-woman-cafe-portrait-by-promptplum.avif')],
                ['value'=>'classic','label'=>'کلاسیک','image'=>asset('assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp')],
            ]],
            ['id' => 'ratio', 'type' => 'aspect_ratio', 'label' => 'نسبت تصویر', 'value' => '4:5', 'options' => [['value'=>'1:1','label'=>'۱:۱'],['value'=>'4:5','label'=>'۴:۵'],['value'=>'9:16','label'=>'۹:۱۶'],['value'=>'16:9','label'=>'۱۶:۹']]],
            ['id' => 'resolution', 'type' => 'resolution', 'label' => 'کیفیت خروجی', 'value' => '2K', 'options' => [['value'=>'1K','label'=>'1K','meta'=>'استاندارد'],['value'=>'2K','label'=>'2K','meta'=>'پیشنهادی'],['value'=>'4K','label'=>'4K','meta'=>'+ ۶ توکن']]],
            ['id' => 'negative', 'type' => 'negative_prompt', 'label' => 'موارد ناخواسته', 'placeholder' => 'مثلاً: عینک، نوشته، تاری...'],
            ['id' => 'seed', 'type' => 'seed', 'label' => 'Seed', 'placeholder' => 'تصادفی'],
            ['id' => 'source_file', 'type' => 'file_upload', 'label' => 'فایل مرجع تکمیلی', 'help' => 'اختیاری'],
        ];
    }

    public function show(Product $product)
    {
        $metricPayload = [
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];
        ProductMetricEvent::create($metricPayload + ['event_type' => 'view']);
        if (request()->query('source') === 'trends') {
            ProductMetricEvent::create($metricPayload + ['event_type' => 'trend_open']);
        }

        $product->loadCount('likedByUsers');

        $similar = Product::where('status', 'active')
            ->where('id', '!=', $product->id)
            ->where('category', $product->category)
            ->latest()->limit(6)->get();

        // وضعیت سیو بودن محصول برای کاربر لاگین‌کرده فعلی (برای رنگ‌آمیزی اولیه دکمه سیو)
        $isSaved = auth()->check() ? auth()->user()->hasSavedProduct($product->id) : false;

        // وضعیت لایک بودن محصول (try/catch: اگر مایگریشن liked_products هنوز اجرا نشده باشد صفحه نمی‌شکند)
        $isLiked = false;
        if (auth()->check()) {
            try { $isLiked = auth()->user()->hasLikedProduct($product->id); } catch (\Throwable $e) {}
        }

        $referralSettings = ReferralSetting::current();
        $productReferralUrl = auth()->check() && $referralSettings->referralIsActive()
            ? route('referral.product', [
                'code' => auth()->user()->referral_code,
                'product' => $product->route_slug,
            ])
            : null;

        return view('app.product', compact(
            'product', 'similar', 'isSaved', 'isLiked', 'referralSettings', 'productReferralUrl'
        ));
    }

    public function generate(GenerateProductRequest $request, Product $product, ProductBuildSchema $schema, ProductPromptBuilder $promptBuilder)
    {
        $user = auth()->user();
        $reservedCredit = 0;
        $order = null;
        $fieldValues = (array) $request->input('fields', []);
        $identityRequested = (bool) $product->identity_preservation && $request->boolean('identity_preservation');
        $creditCost = max(0, (int) ($product->credit_cost ?? 0))
            + $schema->additionalCredit($product, $fieldValues)
            + ($identityRequested ? max(0, (int) $product->identity_credit_cost) : 0);

        // ۰. مدل‌های خروجی چندگانه (Output Variants) — اگر محصول واریانت دارد،
        // کاربر باید حداقل یکی را انتخاب کرده باشد و هزینه توکن در تعداد انتخاب ضرب می‌شود.
        $variantList = $product->outputVariantList();
        $selectedVariants = [];
        if (!empty($variantList)) {
            $requestedKeys = array_map('strval', (array) $request->input('variants', []));
            foreach ($variantList as $v) {
                if (in_array($v['key'], $requestedKeys, true)) {
                    $selectedVariants[] = $v;
                }
            }
            if (empty($selectedVariants)) {
                return response()->json([
                    'success' => false,
                    'message' => 'حداقل یک مدل خروجی را انتخاب کنید.',
                ], 422);
            }
        }
        $runCount = max(1, count($selectedVariants));
        $originalCreditCost = $creditCost * $runCount;
        $totalCreditCost = $originalCreditCost;
        $discount = null;
        $discountCredits = 0;

        if ($request->filled('discount_code')) {
            $code = strtoupper(trim((string) $request->input('discount_code')));
            $discount = Discount::available()->where('code', $code)->first();
            if (!$discount) {
                return response()->json(['success' => false, 'message' => 'کد تخفیف معتبر یا فعال نیست.'], 422);
            }
            if ($discount->first_order_only && Order::where('user_id', $user?->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'این کد فقط برای اولین سفارش قابل استفاده است.'], 422);
            }
            if ($user && Order::where('user_id', $user->id)->where('discount_id', $discount->id)->count() >= $discount->usage_limit_per_user) {
                return response()->json(['success' => false, 'message' => 'سقف استفاده شما از این کد تخفیف تکمیل شده است.'], 422);
            }
            $inScope = $discount->scope === 'all'
                || ($discount->scope === 'products' && in_array($product->id, $discount->product_ids ?? [], true))
                || ($discount->scope === 'categories' && $product->categories()->whereIn('categories.id', $discount->category_ids ?? [])->exists());
            if (!$inScope) {
                return response()->json(['success' => false, 'message' => 'این کد برای محصول انتخاب‌شده قابل استفاده نیست.'], 422);
            }
            $discountCredits = $discount->calculateCredits($originalCreditCost);
            if ($discountCredits < 1) {
                return response()->json(['success' => false, 'message' => 'حداقل اعتبار لازم برای این کد تخفیف تأمین نشده است.'], 422);
            }
            $totalCreditCost = max(0, $originalCreditCost - $discountCredits);
        }

        // ۱. بررسی اعتبار توکن کاربر (بر اساس جمع کل مدل‌های انتخاب‌شده)
        if ($product->pricing_model === 'per_credit' && $totalCreditCost > 0) {
            if (!$user || $user->tokens < $totalCreditCost) {
                return response()->json([
                    'success' => false,
                    'message' => 'توکن‌های شما کافی نیست.',
                ], 402);
            }
        }

        // اصلاح دریافت فایل‌ها بر اساس ساختار ارسالی جاوااسکریپت (uploads)
        $allFiles = $schema->flattenUploads($request);
        if (in_array($product->subject_type, ['face', 'body'], true) && count($allFiles) > 3) {
            return response()->json([
                'success' => false,
                'message' => 'برای محصولات چهره‌محور حداکثر ۳ عکس مرجع قابل استفاده است.',
            ], 422);
        }

        // ۲. بررسی سخت‌گیرانه سقف فضای ذخیره‌سازی (حداکثر ۱۰۰ مگابایت)
        if ($user) {
            $createdImagesSize = $user->generatedImages()->sum('size') ?? 0;
            $personalImagesSize = $user->uploadedImages()->sum('size') ?? 0;
            $currentUsedBytes = $createdImagesSize + $personalImagesSize;

            $newUploadsSize = 0;
            foreach ($allFiles as $file) {
                if ($file) {
                    $newUploadsSize += $file->getSize();
                }
            }

            $maxStorageBytes = 100 * 1024 * 1024;
            $estimatedAiImageSize = (2 * 1024 * 1024) * $runCount;

            if (($currentUsedBytes + $newUploadsSize + $estimatedAiImageSize) > $maxStorageBytes) {
                return response()->json([
                    'success' => false,
                    'message' => 'فضای ذخیره‌سازی ۱۰۰ مگابایتی شما کافی نیست! لطفاً ابتدا فایل‌های قبلی خود را مدیریت یا پاک کنید.',
                ], 400);
            }
        }

        // ۳. ساخت پرامپت نهایی: system_prompt + قالب (با جایگذاری متغیرها) + دستور حفظ هویت
        $finalPrompt = $promptBuilder->build($product, $fieldValues, $identityRequested);

        // ۴. پردازش و ذخیره‌سازی عکس‌های آپلودی کاربر
        $base64Images  = [];
        $uploadedPaths = [];

        foreach ($allFiles as $file) {
            if (!$file) continue;

            $path = $file->store('uploads/personal', 'public');
            $uploadedPaths[] = [
                'path' => $path,
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ];

            $mime = $file->getMimeType();
            if (str_starts_with((string) $mime, 'image/')) {
                $b64 = base64_encode(file_get_contents($file->getRealPath()));
                $base64Images[] = "data:{$mime};base64,{$b64}";
            }
        }

        // ۴.۱ الزام تصویر مرجع برای محصولات هویت‌محور/ویرایشی
        $minRefs = (int) ($product->min_reference_images ?? 0);
        if ($identityRequested && $minRefs > 0 && count($base64Images) < $minRefs) {
            foreach ($uploadedPaths as $up) {
                Storage::disk('public')->delete($up['path']);
            }
            return response()->json([
                'success' => false,
                'message' => "این محصول برای نتیجهٔ دقیق به حداقل {$minRefs} تصویر ورودی نیاز دارد.",
            ], 422);
        }

        // ۵. مشخصات خروجی تصویر هوش مصنوعی
        $schemaFields = collect($schema->fields($product));
        $valueForType = fn (string $type) => (($field = $schemaFields->firstWhere('type', $type)) ? ($fieldValues[$field['id']] ?? null) : null);
        $aspectRatio = $request->input('output.aspect_ratio', $valueForType('aspect_ratio') ?? $product->aspect_ratio ?? '1:1');
        // حالت عادی همیشه Grade B / Medium و حفظ هویت همیشه Grade A / High است.
        $quality = $identityRequested ? '2K' : '1K';

        $executionProduct = $product;
        if ($identityRequested && $product->identity_model) {
            $executionProduct = $product->replicate();
            $executionProduct->primary_model = $product->identity_model;
            $executionProduct->ai_provider = $product->identity_model_provider ?: $product->ai_provider;
            $executionProduct->fallback_models = [];
            $executionProduct->fallback_model_providers = [];
        }

        try {
            // هر درخواست ساخت، یک سفارش قابل‌پیگیری در پنل مدیریت ایجاد می‌کند.
            // این ثبت مستقل از خروجی‌های چندگانه است تا کاربر یک سفارش واحد ببیند.
            $order = Order::create([
                'user_id' => $user?->id,
                'product_id' => $product->id,
                'discount_id' => $discount?->id,
                'status' => 'processing',
                'payment_status' => 'paid',
                'processing_status' => 'processing',
                'original_credits' => $originalCreditCost,
                'discount_credits' => $discountCredits,
                'final_credits' => $totalCreditCost,
                'discount_code' => $discount?->code,
                'ai_model' => $executionProduct->primary_model,
                'attempts' => 1,
                'input_payload' => [
                    'fields' => $fieldValues,
                    'variants' => array_column($selectedVariants, 'key'),
                    'aspect_ratio' => $aspectRatio,
                    'quality' => $quality,
                    'identity_preservation' => $identityRequested,
                ],
                'source' => 'app',
                'paid_at' => now(),
                'processing_started_at' => now(),
            ]);
            $order->recordEvent('created', 'سفارش ثبت شد', 'پردازش سفارش هوش مصنوعی آغاز شد.');

            // رزرو اتمیک اعتبار؛ از ساخت هم‌زمان بیش از موجودی جلوگیری می‌کند.
            if ($product->pricing_model === 'per_credit' && $totalCreditCost > 0) {
                $reserved = \App\Models\User::whereKey($user->id)
                    ->where('tokens', '>=', $totalCreditCost)
                    ->decrement('tokens', $totalCreditCost);
                if ($reserved !== 1) {
                    foreach ($uploadedPaths as $up) Storage::disk('public')->delete($up['path']);
                    $order->update([
                        'status' => 'review',
                        'payment_status' => 'failed',
                        'processing_status' => 'stopped',
                        'error_message' => 'موجودی اعتبار هنگام رزرو نهایی کافی نبود.',
                    ]);
                    $order->recordEvent('payment_failed', 'رزرو اعتبار ناموفق بود', 'موجودی کاربر هم‌زمان با ثبت سفارش تغییر کرده است.');
                    return response()->json(['success' => false, 'message' => 'توکن‌های شما کافی نیست.'], 402);
                }
                $reservedCredit = $totalCreditCost;
            }

            // پیامک خرید موفق نباید در صورت اختلال سرویس پیامک، روند ساخت محصول را متوقف کند.
            if ($user?->phone) {
                $freshUser = $user->fresh();
                app(SmsEventService::class)->send('purchase_success', $user->phone, [
                    'name'=>$user->name, 'phone'=>$user->phone, 'order_number'=>$order->order_number,
                    'product_name'=>$product->name_fa ?? $product->name ?? '', 'amount'=>(string)$totalCreditCost,
                    'balance'=>(string)($freshUser->tokens ?? 0),
                ]);
                app(SmsEventService::class)->notifyLowCredit($freshUser);
            }

            $extraPayload = [];
            if (!empty($base64Images)) {
                $extraPayload['input_references'] = array_map(fn($b64) => [
                    'type'      => 'image_url',
                    'image_url' => ['url' => $b64],
                ], $base64Images);
            }
            if ($identityRequested) {
                $extraPayload['input_fidelity'] = 'high';
            }

            // پارامترهای واقعی مؤثر بر کیفیت — فقط در صورت مقداردهی ارسال می‌شوند
            $userNegativePrompt = $valueForType('negative_prompt');
            if (!empty($userNegativePrompt) || !empty($product->negative_prompt)) {
                $extraPayload['negative_prompt'] = trim(implode(', ', array_filter([$product->negative_prompt, $userNegativePrompt])));
            }
            $userSeed = $valueForType('seed');
            if ($userSeed !== null || !is_null($product->seed)) {
                $extraPayload['seed'] = (int) ($userSeed ?? $product->seed);
            }
            $strength = $valueForType('strength');
            if ($strength !== null) {
                $extraPayload['strength'] = max(0, min(1, ((float) $strength) / 100));
            }
            if (!empty($product->output_format)) {
                $extraPayload['output_format'] = $product->output_format;
            }
            if (is_array($product->provider_options) && !empty($product->provider_options)) {
                $extraPayload['provider'] = $product->provider_options;
            }

            $outputCount = max(1, (int) ($product->output_count ?? 1));

            // ۵.۱ لیست اجراها: بدون واریانت = یک اجرا با پرامپت اصلی (رفتار قبلی دست‌نخورده)؛
            // با واریانت = دقیقاً یک اجرا به‌ازای هر مدل تیک‌خورده، با پرامپت اختصاصی همان مدل.
            $runs = [];
            if (!empty($selectedVariants)) {
                foreach ($selectedVariants as $v) {
                    $variantPrompt = $finalPrompt;
                    if ($v['prompt'] !== '') {
                        $variantPrompt .= "\n\n" . $v['prompt'];
                    } else {
                        $variantPrompt .= "\n\nOutput style/scene variant: " . $v['title'];
                    }
                    $runs[] = ['prompt' => $variantPrompt, 'key' => $v['key'], 'title' => $v['title'], 'n' => 1];
                }
            } else {
                $runs[] = ['prompt' => $finalPrompt, 'key' => null, 'title' => null, 'n' => $outputCount];
            }

            $generated = [];   // [{key, title, url, path, size, cost, prompt}]
            $failed    = [];
            $usedModels = [];

            foreach ($runs as $run) {
                try {
                    $attempt = $this->openRouter->generateForProduct(
                        $executionProduct,
                        $run['prompt'],
                        $quality,
                        $aspectRatio,
                        $run['n'],
                        $extraPayload
                    );
                    $result = $attempt['data'];
                    $usedModels[] = $attempt['model'];

                    // ۶. ذخیره همه تصاویر پاسخ (نه فقط اولین تصویر آرایه data)
                    $items = !empty($result['data']) && is_array($result['data']) ? $result['data'] : [$result];
                    $perImageApiCost = ((float) ($result['usage']['cost'] ?? 0)) / max(1, count($items));
                    foreach ($items as $item) {
                        $singleResult = isset($item['b64_json']) || isset($item['url']) ? ['data' => [$item]] : $item;
                        $imageUrl  = $this->saveGeneratedImage($singleResult);
                        $imagePath = $this->urlToStoragePath($imageUrl);
                        $imageSize = Storage::disk('public')->exists($imagePath)
                            ? Storage::disk('public')->size($imagePath)
                            : 1024 * 1024;

                        $generated[] = [
                            'key'    => $run['key'],
                            'title'  => $run['title'],
                            'url'    => $imageUrl,
                            'path'   => $imagePath,
                            'size'   => $imageSize,
                            'cost'   => $perImageApiCost,
                            'prompt' => $run['prompt'],
                        ];
                    }
                } catch (Exception $e) {
                    Log::error('ProductGenerateController Variant Error [' . ($run['title'] ?? 'default') . ']: ' . $e->getMessage());
                    $failed[] = $run['title'] ?? 'خروجی';
                    // اگر تک‌اجرایی بود (رفتار قبلی)، خطا مثل قبل به بیرون پرتاب می‌شود
                    if (count($runs) === 1) {
                        throw $e;
                    }
                }
            }

            if (empty($generated)) {
                throw new Exception('هیچ‌کدام از مدل‌های خروجی انتخاب‌شده با موفقیت ساخته نشد. لطفاً دوباره تلاش کنید.');
            }

            // ۷. ثبت نهایی سوابق در دیتابیس در صورت لاگین بودن کاربر
            if ($user) {
                foreach ($uploadedPaths as $up) {
                    UserUpload::create([
                        'user_id'   => $user->id,
                        'file_path' => $up['path'],
                        'size'      => $up['size'],
                        'mime_type' => $up['mime'],
                    ]);
                }

                foreach ($generated as $g) {
                    GeneratedImage::create([
                        'user_id'     => $user->id,
                        'product_id'  => $product->id,
                        'image_path'  => $g['path'],
                        'user_prompt' => $g['prompt'],
                        'cost'        => $g['cost'],
                        'size'        => $g['size'],
                    ]);
                }

            }

            // اگر بعضی واریانت‌ها شکست خوردند، اعتبار همان خروجی‌ها بازگردانده می‌شود.
            $actualOriginalCredit = $product->pricing_model === 'per_credit' ? $creditCost * count($generated) : 0;
            $actualDiscount = $discount?->calculateCredits($actualOriginalCredit) ?? 0;
            $actualCredit = max(0, $actualOriginalCredit - $actualDiscount);
            if ($reservedCredit > $actualCredit) {
                $user->increment('tokens', $reservedCredit - $actualCredit);
                $reservedCredit = $actualCredit;
            }

            $failedMsg = !empty($failed)
                ? 'ساخت این مدل‌ها ناموفق بود: ' . implode('، ', $failed)
                : null;

            $order?->update([
                'status' => 'completed',
                'processing_status' => 'completed',
                'ai_model' => $usedModels[0] ?? $executionProduct->primary_model,
                'final_credits' => $actualCredit,
                'original_credits' => $actualOriginalCredit,
                'discount_credits' => $actualDiscount,
                'output_payload' => array_map(fn ($g) => ['key' => $g['key'], 'title' => $g['title'], 'path' => $g['path']], $generated),
                'completed_at' => now(),
                'processing_duration_ms' => $order?->processing_started_at ? $order->processing_started_at->diffInMilliseconds(now()) : null,
            ]);
            $order?->recordEvent('completed', 'پردازش با موفقیت تکمیل شد', $failedMsg);
            if ($user?->phone && $order) app(SmsEventService::class)->send('order_completed', $user->phone, [
                'name'=>$user->name, 'phone'=>$user->phone, 'order_number'=>$order->order_number,
                'product_name'=>$product->name_fa ?? $product->name ?? '', 'balance'=>(string)($user->fresh()->tokens ?? 0),
            ]);
            if ($discount) $discount->increment('used_count');

            return response()->json([
                'success'          => true,
                'image_url'        => $generated[0]['url'],
                'images'           => array_map(fn ($g) => [
                    'key'   => $g['key'],
                    'title' => $g['title'],
                    'url'   => $g['url'],
                ], $generated),
                'failed_message'   => $failedMsg,
                'used_model'       => $usedModels[0] ?? $executionProduct->primary_model,
                'remaining_tokens' => $user ? $user->fresh()->tokens : 0,
            ]);

        } catch (Exception $e) {
            if ($reservedCredit > 0 && $user) {
                $user->increment('tokens', $reservedCredit);
            }
            foreach ($uploadedPaths as $up) {
                Storage::disk('public')->delete($up['path']);
            }
            Log::error('ProductGenerateController Error: ' . $e->getMessage());
            if ($order) {
                $order->update([
                    'status' => 'review', 'processing_status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'processing_duration_ms' => $order->processing_started_at ? $order->processing_started_at->diffInMilliseconds(now()) : null,
                ]);
                $order->recordEvent('failed', 'پردازش ناموفق بود', $e->getMessage());
            }
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * مدیریت هوشمند پاسخ‌های مبتنی بر URL یا Base64 از سمت مدل‌های OpenRouter
     */
    protected function saveGeneratedImage(array $responseData): string
    {
        // ۱. تلاش برای پیدا کردن ساختار Base64 مرسوم
        $base64Image = $responseData['data'][0]['b64_json']
            ?? $responseData[0]['b64_json']
            ?? null;

        $filename = 'generated/' . uniqid('gen_') . '.png';

        if ($base64Image) {
            if (str_contains($base64Image, 'base64,')) {
                $base64Image = explode('base64,', $base64Image)[1];
            }
            $binary = base64_decode(trim($base64Image), true);
            if ($binary === false) throw new Exception('خطا در رمزگشایی کدهای مربوط به تصویر Base64.');
            
            Storage::disk('public')->put($filename, $binary);
            return asset('storage/' . $filename);
        }

        // ۲. پشتیبانی از ساختارهای مبتنی بر لینک مستقیم (بسیاری از پلتفرم‌های OpenRouter لینک تصویر برمی‌گردانند)
        $directUrl = $responseData['data'][0]['url'] 
            ?? $responseData[0]['url'] 
            ?? null;
        $downloadHeaders = $responseData['data'][0]['headers']
            ?? $responseData[0]['headers']
            ?? [];

        if ($directUrl) {
            try {
                $download = Http::withHeaders(is_array($downloadHeaders) ? $downloadHeaders : [])
                    ->connectTimeout(15)->timeout(120)->get($directUrl);
                if ($download->successful()) {
                    Storage::disk('public')->put($filename, $download->body());
                    return asset('storage/' . $filename);
                }
            } catch (Exception $e) {
                Log::warning("امکان دانلود مستقیم تصویر فراهم نشد، بازگشت لینک اصلی: " . $e->getMessage());
                return $directUrl; // در صورت عدم امکان دانلود، خود آدرس مستقیم را برگردان
            }
            return $directUrl;
        }

        throw new Exception('هیچ داده تصویری معتبری (بایری یا لینک خروجی) در پاسخ سرور یافت نشد. پاسخ خام: ' . json_encode($responseData));
    }

    protected function urlToStoragePath(string $url): string
    {
        $base = asset('storage/');
        return str_replace($base, '', $url);
    }
}
