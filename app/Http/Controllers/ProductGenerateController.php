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

        // ۲. بررسی سخت‌گیرانه سقف فضای ذخیره‌سازی (حداکثر ۱۰۰ مگابایت)
        if ($user) {
            $createdImagesSize = $user->generatedImages()->sum('size') ?? 0;
            $personalImagesSize = $user->uploadedImages()->sum('size') ?? 0;
            $currentUsedBytes = $createdImagesSize + $personalImagesSize;

            // محاسبه حجم فایل‌های جدیدی که کاربر همین الان می‌خواهد آپلود کند
            $newUploadsSize = 0;
            if ($request->hasFile('uploads')) {
                foreach ($request->file('uploads') as $file) {
                    if ($file) {
                        $newUploadsSize += $file->getSize();
                    }
                }
            }

            $maxStorageBytes = 100 * 1024 * 1024; // تبدیل ۱۰۰ مگابایت به بایت
            $estimatedAiImageSize = 2 * 1024 * 1024; // پیش‌بینی ۲ مگابایت برای عکس خروجی هوش مصنوعی

            if (($currentUsedBytes + $newUploadsSize + $estimatedAiImageSize) > $maxStorageBytes) {
                return response()->json([
                    'success' => false,
                    'message' => 'فضای ذخیره‌سازی ۱۰۰ مگابایتی شما کافی نیست! لطفاً ابتدا فایل‌های قبلی خود را مدیریت یا پاک کنید.',
                ], 400);
            }
        }

        // ۳. جایگذاری فیلدها در قالب پرامپت
        $finalPrompt = $product->prompt_template;
        foreach ($request->input('fields', []) as $key => $value) {
            $finalPrompt = str_replace('{' . $key . '}', $value, $finalPrompt);
        }

        // ۴. پردازش و ذخیره‌سازی عکس‌های آپلودی کاربر
        $base64Images  = [];
        $uploadedPaths = []; 

        if ($request->hasFile('uploads')) {
            foreach ($request->file('uploads') as $file) {
                if (!$file) continue;

                // ذخیره فیزیکی روی دیسک پابلیک
                $path = $file->store('uploads/personal', 'public');
                $uploadedPaths[] = [
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ];

                // تبدیل به base64 جهت ارسال به سرویس تصویرساز
                $mime           = $file->getMimeType();
                $b64            = base64_encode(file_get_contents($file->getRealPath()));
                $base64Images[] = "data:{$mime};base64,{$b64}";
            }
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
                $product->primary_model,
                $finalPrompt,
                $quality,
                $aspectRatio,
                1,
                $extraPayload
            );

            // ۶. ذخیره فایل تصویر خروجی روی دیسک سرور
            $imageUrl  = $this->saveGeneratedImage($result);
            $imagePath = $this->urlToStoragePath($imageUrl);
            $imageSize = Storage::disk('public')->size($imagePath);

            // ۷. ثبت نهایی سوابق در دیتابیس در صورت لاگین بودن کاربر
            if ($user) {
                // ثبت عکس‌های شخصی آپلود شده
                foreach ($uploadedPaths as $up) {
                    UserUpload::create([
                        'user_id'   => $user->id,
                        'file_path' => $up['path'],
                        'size'      => $up['size'],
                        'mime_type' => $up['mime'],
                    ]);
                }

                // ثبت تصویر خروجی هوش مصنوعی با فیلد صحیح image_path
                GeneratedImage::create([
                    'user_id'     => $user->id,
                    'product_id'  => $product->id,
                    'image_path'  => $imagePath,
                    'user_prompt' => $finalPrompt,
                    'cost'        => $result['usage']['cost'] ?? 0,
                    'size'        => $imageSize,
                ]);

                // کسر توکن مصرفی محصول از اکانت کاربر
                if ($product->pricing_model === 'per_credit' && $creditCost > 0) {
                    $user->decrement('tokens', $creditCost);
                }
            }

            return response()->json([
                'success'          => true,
                'image_url'        => $imageUrl,
                'used_model'       => $product->primary_model,
                'remaining_tokens' => $user?->fresh()->tokens,
            ]);

        } catch (Exception $e) {
            // در صورت فیل شدن ساخت تصویر، فایل‌های موقت آپلود شده پاک شوند تا فضا اشغال نشود
            foreach ($uploadedPaths as $up) {
                Storage::disk('public')->delete($up['path']);
            }
            Log::error('ProductGenerateController: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 503);
        }
    }

    protected function saveGeneratedImage(array $responseData): string
    {
        $base64Image = $responseData['data'][0]['b64_json']
            ?? $responseData[0]['b64_json']
            ?? null;

        if (!$base64Image) {
            throw new Exception('تصویری در پاسخ هوش مصنوعی یافت نشد.');
        }

        if (str_contains($base64Image, 'base64,')) {
            $base64Image = explode('base64,', $base64Image)[1];
        }

        $binary = base64_decode(trim($base64Image), true);
        if ($binary === false) throw new Exception('خطا در رمزگشایی تصویر.');

        $filename = 'generated/' . uniqid('gen_') . '.png';
        Storage::disk('public')->put($filename, $binary);

        return asset('storage/' . $filename);
    }

    protected function urlToStoragePath(string $url): string
    {
        $base = asset('storage/');
        return str_replace($base, '', $url);
    }
}