<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Generation;
use App\Services\LogService; 
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Services\TokenBalanceService;
use Illuminate\Validation\ValidationException;

class GenerationController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::where('is_active', true)->get();
        return response()->json(['products' => $products]);
    }

    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'image' => 'required|image|max:10240',
        ]);

        // فرضی: هزینه تولید هر تصویر ۱۰ توکن است
        $tokenCost = 10; 

        // 🔴 بررسی و کسر توکن مستقیماً از جدول کاربران (User) برای محاسبه مصرف شده
        if (Auth::check()) {
            $user = Auth::user();
            
            try {
                app(TokenBalanceService::class)->debit($user, $tokenCost);
            } catch (ValidationException) {
                return response()->json(['error' => 'توکن شما کافی نیست. لطفا حساب خود را شارژ کنید.'], 403);
            }
        }

        // ذخیره‌سازی تصویر ورودی کاربر در هاست
        $inputPath = $request->file('image')->store('inputs', 'public');

        // استفاده از سرویس مرکزی لاگ برای ثبت درخواست با وضعیت اولیه pending
        $generation = LogService::log(
            prompt: "تولید تصویر خودکار", 
            inputImage: $inputPath,
            productId: $request->product_id
        );

        $generationId = $generation->id;

        // ارسال کار به صف پردازش بک‌گراند پس‌زمینه
        dispatch(new \App\Jobs\ProcessImageJob($generationId));

        return response()->json([
            'message' => 'تصویر با موفقیت در صف پردازش قرار گرفت و توکن کسر شد.',
            'generation_id' => $generationId
        ]);
    }

    /**
     * متد چک کردن وضعیت هوشمند فاز اول
     */
    public function checkStatus(string $id): JsonResponse
    {
        $generation = Generation::find($id);
        if (!$generation) {
            return response()->json(['error' => 'رکورد یافت نشد'], 404);
        }

        if ($generation->status === 'completed') {
            
            $imageUrl = filter_var($generation->output_image, FILTER_VALIDATE_URL) 
                ? $generation->output_image 
                : asset('storage/' . $generation->output_image);
            
            if (!Auth::check()) {
                return response()->json([
                    'status' => 'completed',
                    'is_logged_in' => false,
                    'url' => $imageUrl,
                    'blur' => true,
                    'message' => 'برای دیدن تصویر بدون افکت تار، ثبت نام کنید.'
                ]);
            }

            return response()->json([
                'status' => 'completed',
                'is_logged_in' => true,
                'url' => $imageUrl
            ]);
        }

        return response()->json(['status' => $generation->status]);
    }
}
