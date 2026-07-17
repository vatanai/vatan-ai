<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\GeneratedImage;
use App\Models\UserUpload;
use App\Services\OpenRouterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class ProductGenerateController extends Controller
{
    public function __construct(protected OpenRouterService $openRouter)
    {
    }

    public function create(Request $request)
    {
        $slug = $request->query('product');
        $product = $slug
            ? Product::where('slug', $slug)->where('status', 'active')->first()
            : null;
        return view('app.create', compact('product'));
    }

    public function show(Product $product)
    {
        $similar = Product::where('status', 'active')
            ->where('id', '!=', $product->id)
            ->where('category', $product->category)
            ->latest()->limit(6)->get();

        // وضعیت سیو بودن محصول برای کاربر لاگین‌کرده فعلی (برای رنگ‌آمیزی اولیه دکمه سیو)
        $isSaved = auth()->check() ? auth()->user()->hasSavedProduct($product->id) : false;

        return view('app.product', compact('product', 'similar', 'isSaved'));
    }

    public function generate(Request $request, Product $product)
    {
        $user = auth()->user();
        $creditCost = $product->credit_cost ?? 0;

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
        $totalCreditCost = $creditCost * $runCount;

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
        $allFiles = [];
        if ($request->hasFile('uploads')) {
            $filesInput = $request->file('uploads');
            $allFiles = is_array($filesInput) ? $filesInput : [$filesInput];
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
        $templatePrompt = $product->prompt_template ?? 'Create a high quality image.';
        foreach ($request->input('fields', []) as $key => $value) {
            $replacement = is_array($value) ? implode(', ', $value) : (string) $value;
            $templatePrompt = str_replace('{' . $key . '}', $replacement, $templatePrompt);
        }

        $promptParts = [];
        if (!empty($product->system_prompt)) {
            $promptParts[] = trim($product->system_prompt);
        }
        $promptParts[] = trim($templatePrompt);

        // اگر محصول هویت‌محور است، دستور حفظ چهره/هیکل به پرامپت افزوده می‌شود
        if ($product->identity_preservation) {
            if (!empty($product->identity_instructions)) {
                $promptParts[] = trim($product->identity_instructions);
            } else {
                $idText = 'Preserve the exact facial identity, features, and likeness of the person in the reference image(s). The generated face must clearly and accurately resemble the same person.';
                if ($product->preserve_body) {
                    $idText .= ' Also keep the same body shape, physique, and proportions.';
                }
                $promptParts[] = $idText;
            }
        }

        $finalPrompt = implode("\n\n", array_filter($promptParts));

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

            $mime           = $file->getMimeType();
            $b64            = base64_encode(file_get_contents($file->getRealPath()));
            $base64Images[] = "data:{$mime};base64,{$b64}";
        }

        // ۴.۱ الزام تصویر مرجع برای محصولات هویت‌محور/ویرایشی
        $minRefs = (int) ($product->min_reference_images ?? 0);
        if ($minRefs > 0 && count($base64Images) < $minRefs) {
            foreach ($uploadedPaths as $up) {
                Storage::disk('public')->delete($up['path']);
            }
            return response()->json([
                'success' => false,
                'message' => "این محصول برای نتیجهٔ دقیق به حداقل {$minRefs} تصویر ورودی نیاز دارد.",
            ], 422);
        }

        // ۵. مشخصات خروجی تصویر هوش مصنوعی
        $aspectRatio = $request->input('output.aspect_ratio', $product->aspect_ratio ?? '1:1');
        $quality     = $request->input('output.quality', '1K');

        try {
            $extraPayload = [];
            if (!empty($base64Images)) {
                $extraPayload['input_references'] = array_map(fn($b64) => [
                    'type'      => 'image_url',
                    'image_url' => ['url' => $b64],
                ], $base64Images);
            }

            // پارامترهای واقعی مؤثر بر کیفیت — فقط در صورت مقداردهی ارسال می‌شوند
            if (!empty($product->negative_prompt)) {
                $extraPayload['negative_prompt'] = $product->negative_prompt;
            }
            if (!is_null($product->seed)) {
                $extraPayload['seed'] = (int) $product->seed;
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

            foreach ($runs as $run) {
                try {
                    $result = $this->openRouter->generateImageFromPrompt(
                        $product->primary_model ?? 'stabilityai/stable-diffusion-xl',
                        $run['prompt'],
                        $quality,
                        $aspectRatio,
                        $run['n'],
                        $extraPayload
                    );

                    // ۶. ذخیره فایل تصویر خروجی روی دیسک سرور
                    $imageUrl  = $this->saveGeneratedImage($result);
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
                        'cost'   => $result['usage']['cost'] ?? 0,
                        'prompt' => $run['prompt'],
                    ];
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

                // کسر توکن فقط به‌ازای خروجی‌هایی که واقعاً ساخته شدند
                if ($product->pricing_model === 'per_credit' && $creditCost > 0) {
                    $user->decrement('tokens', $creditCost * count($generated));
                }
            }

            $failedMsg = !empty($failed)
                ? 'ساخت این مدل‌ها ناموفق بود: ' . implode('، ', $failed)
                : null;

            return response()->json([
                'success'          => true,
                'image_url'        => $generated[0]['url'],
                'images'           => array_map(fn ($g) => [
                    'key'   => $g['key'],
                    'title' => $g['title'],
                    'url'   => $g['url'],
                ], $generated),
                'failed_message'   => $failedMsg,
                'used_model'       => $product->primary_model,
                'remaining_tokens' => $user ? $user->fresh()->tokens : 0,
            ]);

        } catch (Exception $e) {
            foreach ($uploadedPaths as $up) {
                Storage::disk('public')->delete($up['path']);
            }
            Log::error('ProductGenerateController Error: ' . $e->getMessage());
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

        if ($directUrl) {
            try {
                $imageContent = file_get_contents($directUrl);
                if ($imageContent !== false) {
                    Storage::disk('public')->put($filename, $imageContent);
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