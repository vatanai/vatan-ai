<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Prompt;

class FrontPromptController extends Controller
{
    /**
     * نمایش صفحه اصلی پرامپت و استایل
     */
    public function show($id)
    {
        $prompt = Prompt::findOrFail($id);
        return view('prompts.show', compact('prompt'));
    }

    /**
     * پردازش تصویر آژاکس بر اساس مستندات رسمی ۲۰۲۶ OpenRouter Images API
     */
    public function generateImage(Request $request, $id)
    {
        $promptModel = Prompt::findOrFail($id);

        // ۱. ولیدیشن دقیق فیلدهای ارسالی از کامپوننت آپلودر شما
        $request->validate([
            'user_image'        => 'required|image|mimes:jpeg,png,jpg,webp|max:12288',
            'additional_prompt' => 'nullable|string|max:1000'
        ]);

        // ۲. متد هوشمند صید کلید API (بررسی لاراول کانفیگ -> متغیرهای عمومی لیارا)
        $apiKey = config('services.openrouter.api_key') 
                  ?? env('OPENROUTER_API_KEY') 
                  ?? env('OPENROUTER_KEY');

        if (!$apiKey) {
            Log::critical('OpenRouter API Key Missing on Liara Environment.');
            return response()->json([
                'success' => false,
                'message' => 'کلید اتصال به هوش مصنوعی (OPENROUTER_API_KEY) در متغیرهای برنامه لیارا تنظیم نشده است.'
            ], 500);
        }

        // مدل پیش‌فرض تصویرساز طبق داکیومنت شما
        $model = config('services.openrouter.model', 'google/gemini-3.1-flash-image');

        try {
            // ۳. دریافت فایل آپلود شده فیزیکی کاربر
            $imageFile = $request->file('user_image');
            
            // تبدیل عکس پایه کاربر به دیتا یو‌آرآل Base64 طبق ساختار input_references مستندات
            $imageData = base64_encode(file_get_contents($imageFile->getRealPath()));
            $mimeType  = $imageFile->getClientMimeType();
            $base64ImageReference = "data:{$mimeType};base64,{$imageData}";

            // ۴. ترکیب عنوان سبک پرامپت دیتابیس + دستور تکست‌اریای کاربر
            $styleTitle = $promptModel->name ?? $promptModel->title;
            $userNotes  = $request->input('additional_prompt');
            
            $finalPrompt = "Apply this visual style: {$styleTitle}.";
            if (!empty($userNotes)) {
                $finalPrompt .= " Additional instructions: {$userNotes}";
            }

            // ۵. شلیک ریکوئست دقیقاً مطابق با آخرین مستندات ارسالی شما
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
                'HTTP-Referer'  => config('app.url', 'https://aivatan.liara.run'),
                'X-Title'       => config('app.name', 'Uniset AI'),
            ])->timeout(150)->post('https://openrouter.ai/api/v1/images', [
                'model'        => $model,
                'prompt'       => $finalPrompt,
                'resolution'   => '1K',
                'aspect_ratio' => '1:1',
                'input_references' => [
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $base64ImageReference // ارسال ایمیج رفرنس به صورت استاندارد داکیومنت
                        ]
                    ]
                ]
            ]);

            // ۶. بررسی وضعیت خطا یا عدم دسترسی به کلاینت اوپن‌روتر
            if ($response->failed()) {
                Log::error('OpenRouter Response Error Body: ' . $response->body());
                $errorData = $response->json();
                $errMessage = $errorData['error']['message'] ?? 'خطای غیرمنتظره در سرورهای هوش مصنوعی.';
                return response()->json([
                    'success' => false,
                    'message' => 'پاسخ ناموفق API: ' . $errMessage
                ], 500);
            }

            $result = $response->json();
            
            // 🟢 اصلاح ساختاری بر اساس مستندات شما: استخراج مستقیم داده از b64_json
            $rawBase64Image = $result['data'][0]['b64_json'] ?? null;

            if (!$rawBase64Image) {
                Log::error('Structure mismatch. Response: ' . json_encode($result));
                return response()->json([
                    'success' => false,
                    'message' => 'ساختار داده‌های خروجی با استاندارد مدل سازگار نیست.'
                ], 500);
            }

            // ۷. دیکود کردن دیتای تصویر فشرده شده بیس۶۴ و نجات از خطای دیسک خواندنی
            // پاکسازی هدرهای احتمالی چسبیده به بیس۶۴
            if (str_contains($rawBase64Image, ',')) {
                $rawBase64Image = explode(',', $rawBase64Image)[1];
            }
            $fileContents = base64_decode($rawBase64Image);

            // ایجاد مسیر امن ذخیره‌سازی روی دیسک دپلو شده لیارا
            $aiImageName = 'uniset_' . time() . '_' . uniqid() . '.png';
            $diskPath    = 'uploads/prompts/' . $aiImageName;

            // ذخیره نهایی فیزیکی روی دیسک پابلیک لاراول
            Storage::disk('public')->put($diskPath, $fileContents);

            // ۸. بازگرداندن آدرس مستقیم و نهایی بدون ارور ۴۰۴ برای جایگذاری در فرانت آژاکس
            return response()->json([
                'success'   => true,
                'image_url' => asset('storage/' . $diskPath),
                'cost'      => $result['usage']['cost'] ?? 0
            ]);

        } catch (\Exception $e) {
            Log::error('Critical Exception in FrontPromptController: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'خطای پردازش داخلی سرور: ' . $e->getMessage()
            ], 500);
        }
    }
}