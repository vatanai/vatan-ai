<?php

namespace App\Jobs;

use App\Models\Generation;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class ProcessImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $generationId;

    public function __construct(int $generationId)
    {
        $this->generationId = $generationId;
    }

    public function handle(): void
    {
        $generation = Generation::with('product')->find($this->generationId);
        if (!$generation) return;

        // تغییر وضعیت به در حال پردازش (میکروتسک ۳.۲)
        $generation->update(['status' => 'processing']);

        try {
            $apiKey = 'sk-Jr0t9C4ysyU32JuukeK0AdhQVWZwdDOM';
            $imagePath = storage_path('app/public/' . $generation->input_image);

            if (!File::exists($imagePath)) {
                $generation->update(['status' => 'failed']);
                return;
            }

            // ارسال تصویر و پرامپت به API نانو بنانا با ساختار مالتی‌پارت
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ])->attach(
                'image', 
                file_get_contents($imagePath), 
                basename($imagePath)
            )->post('https://nanobananaapi.dev/v1/image/transform', [
                'prompt' => $generation->product->prompt,
                'strength' => 0.75, // میزان تاثیرپذیری از سبک
            ]);

            if ($response->successful()) {
                $result = $response->json();
                
                // فرض بر اینکه API آدرس عکس تولید شده یا داده باینری برمی‌گرداند
                // اگر API لینک مستقیم عکس رو داد (مثلاً در فیلد url):
                $generatedImageUrl = $result['url'] ?? null;
                
                if ($generatedImageUrl) {
                    $imageContent = Http::get($generatedImageUrl)->body();
                    $outputFileName = 'outputs/' . uniqid() . '.jpg';
                    Storage::disk('public')->put($outputFileName, $imageContent);

                    // ۱. آپدیت وضعیت تولید به اتمام رسیده
                    $generation->update([
                        'output_image' => $outputFileName,
                        'status' => 'completed'
                    ]);

                    // ۲. هزینه پیش از ارسال job از موجودی رسمی users.tokens کسر شده است.
                    // اینجا فقط تراکنش مصرف ثبت می‌شود تا دوباره‌کسر اتفاق نیفتد.
                    if ($generation->user_id) {
                        Transaction::create([
                            'user_id' => $generation->user_id,
                            'amount' => -10,
                            'type' => 'usage',
                            'description' => 'تولید تصویر نانو بنانا در سبک ' . $generation->product->name
                        ]);
                    }

                    // ۳. حذف عکس ورودی اصلی برای خلوت ماندن هارد سرور (بند ۳.۲)
                    @unlink($imagePath);
                } else {
                    $generation->update(['status' => 'failed']);
                }

            } else {
                $generation->update(['status' => 'failed']);
            }
        } catch (\Exception $e) {
            $generation->update(['status' => 'failed']);
        }
    }
}
