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
        if (!$slug) abort(404);
        $product = Product::where('slug', $slug)->where('status', 'active')->firstOrFail();
        return view('app.create', compact('product'));
    }

    public function show(Product $product)
    {
        $similar = Product::where('status', 'active')
            ->where('id', '!=', $product->id)
            ->where('category', $product->category)
            ->latest()->limit(6)->get();

        return view('app.product', compact('product', 'similar'));
    }

    public function generate(Request $request, Product $product)
    {
        $user = auth()->user();
        $creditCost = $product->credit_cost ?? 0;

        // ۱. بررسی اعتبار توکن کاربر
        if ($product->pricing_model === 'per_credit' && $creditCost > 0) {
            if (!$user || $user->tokens < $creditCost) {
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
            $estimatedAiImageSize = 2 * 1024 * 1024; 

            if (($currentUsedBytes + $newUploadsSize + $estimatedAiImageSize) > $maxStorageBytes) {
                return response()->json([
                    'success' => false,
                    'message' => 'فضای ذخیره‌سازی ۱۰۰ مگابایتی شما کافی نیست! لطفاً ابتدا فایل‌های قبلی خود را مدیریت یا پاک کنید.',
                ], 400);
            }
        }

        // ۳. جایگذاری فیلدها در قالب پرامپت
        $finalPrompt = $product->prompt_template ?? 'تولید تصویر خلاقانه محصول';
        foreach ($request->input('fields', []) as $key => $value) {
            $finalPrompt = str_replace('{' . $key . '}', $value, $finalPrompt);
        }

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

        // ۵. مشخصات خروجی تصویر هوش مصنوعی
        $aspectRatio = $request->input('output.aspect_ratio', '1:1');
        $quality     = $request->input('output.quality', '1K');

        try {
            $extraPayload = [];
            if (!empty($base64Images)) {
                $extraPayload['input_references'] = array_map(fn($b64) => [
                    'type'      => 'image_url',
                    'image_url' => ['url' => $b64],
                ], $base64Images);
            }

            // درخواست خروجی از OpenRouter
            $result = $this->openRouter->generateImageFromPrompt(
                $product->primary_model ?? 'stabilityai/stable-diffusion-xl',
                $finalPrompt,
                $quality,
                $aspectRatio,
                1,
                $extraPayload
            );

            // ۶. ذخیره فایل تصویر خروجی روی دیسک سرور (متد اصلاح شده افزوده شد)
            $imageUrl  = $this->saveGeneratedImage($result);
            $imagePath = $this->urlToStoragePath($imageUrl);
            
            // دریافت حجم فایل ذخیره شده به صورت امن
            $imageSize = Storage::disk('public')->exists($imagePath) 
                ? Storage::disk('public')->size($imagePath) 
                : 1024 * 1024;

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

                GeneratedImage::create([
                    'user_id'     => $user->id,
                    'product_id'  => $product->id,
                    'image_path'  => $imagePath,
                    'user_prompt' => $finalPrompt,
                    'cost'        => $result['usage']['cost'] ?? 0,
                    'size'        => $imageSize,
                ]);

                if ($product->pricing_model === 'per_credit' && $creditCost > 0) {
                    $user->decrement('tokens', $creditCost);
                }
            }

            return response()->json([
                'success'          => true,
                'image_url'        => $imageUrl,
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