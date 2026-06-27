<?php

namespace App\Services;

use App\Models\Generation;
use Illuminate\Support\Facades\Auth;

class LogService
{
    /**
     * ثبت هوشمند لاگ به همراه ساختار JSON برای عیب‌یابی دقیق‌تر
     */
    public static function log($prompt, $inputImage = null, $outputImage = null, $status = 'completed', $extraData = [])
    {
        $userId = Auth::id() ?? auth('sanctum')->id() ?? auth('api')->id();

        // 🟢 ساخت یک پکیج اطلاعاتی برای ذخیره به صورت JSON
        $jsonData = [
            'raw_prompt'   => $prompt,
            'client_ip'    => request()->ip(),
            'user_agent'   => request()->userAgent(),
            'timestamp'    => now()->toIso8601String(),
            'meta'         => $extraData // اطلاعات اضافی مثل خطاهای API یا پارامترها
        ];

        return Generation::create([
            'user_id'      => $userId,
            'product_id'   => request()->product_id ?? null,
            // تبدیل آرایه به رشته جی‌سان ولید با کاراکترهای یونیکد (فارسی)
            'prompt'       => json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'input_image'  => $inputImage,
            'output_image' => $outputImage,
            'status'       => $status
        ]);
    }
}