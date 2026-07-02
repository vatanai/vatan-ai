<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Generation;
use App\Models\Wallet;
use App\Jobs\ProcessImageJob;
use App\Services\LogService; // 🟢 اضافه شد: اتصال به سرویس مرکزی لاگ
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

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

        if (Auth::check()) {
            $wallet = Wallet::where('user_id', Auth::id())->first();
            if (!$wallet || $wallet->tokens_balance < 10) {
                return response()->json(['error' => 'توکن شما کافی نیست.'], 403);
            }
        }

        // ذخیره‌سازی تصویر ورودی کاربر در هاست
        $inputPath = $request->file('image')->store('inputs', 'public');

        // 🟢 اصلاح شد: استفاده از سرویس مرکزی لاگ برای ثبت درخواست با وضعیت اولیه pending
        // این کار باعث میشه رکورد بلافاصله در پنل ادمین کاربر قابل مشاهده باشه (حتی در حین پردازش)
        $generation = LogService::log(
            prompt: "پردازش پیشرفته محصول با شناسه: " . $request->product_id,
            inputImage: $inputPath,
            outputImage: null, // چون هنوز پردازش شروع نشده خروجی نداریم
            status: 'pending'  // وضعیت اولیه در صف انتظار
        );

        // 🟢 گرفتن آیدی رکورد لاگ ساخته شده
        $generationId = $generation?->id ?? Generation::latest()->first()?->id;

        // ذخیره شناسه در سشن (برای زمانی که کاربر مهمان است و بعداً می‌خواهد ثبت‌نام کند)
        session(['latest_generation_id' => $generationId]);

        // ارسال به جاب پردازش تصویر در پس‌زمینه
        ProcessImageJob::dispatch($generationId);

        return response()->json([
            'message' => 'تصویر با موفقیت در صف پردازش قرار گرفت.',
            'generation_id' => $generationId
        ]);
    }

    /**
     * متد چک کردن وضعیت هوشمند فاز اول (بند ۳.۳)
     */
    public function checkStatus(string $id): JsonResponse
    {
        $generation = Generation::find($id);
        if (!$generation) {
            return response()->json(['error' => 'رکورد یافت نشد'], 404);
        }

        if ($generation->status === 'completed') {
            
            // بررسی اینکه آدرس از قبل یک لینک وب (مانند کدهای فال/اوپن‌روتر) است یا فایل لوکال استوریج
            $imageUrl = filter_var($generation->output_image, FILTER_VALIDATE_URL) 
                ? $generation->output_image 
                : asset('storage/' . $generation->output_image);
            
            // اگه کاربر ثبت نام نکرده، فلگ بلور رو بفرست تا فرانت تار نشانش بده
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