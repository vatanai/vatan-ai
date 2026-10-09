<?php

namespace App\Http\Controllers\SmartInstagram;

use App\Http\Controllers\Controller;
use App\Services\SmartInstagram\Telegram\InstagramTelegramBot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** وب‌هوک بات تلگرام «ثبت پست» — فقط با سکرت هدر تلگرام پذیرفته می‌شود. */
class TelegramBotWebhookController extends Controller
{
    public function __invoke(Request $request, InstagramTelegramBot $bot): JsonResponse
    {
        if (!$bot->configured() || !hash_equals($bot->webhookSecret(), (string) $request->header('X-Telegram-Bot-Api-Secret-Token', ''))) {
            return response()->json(['ok' => false], 403);
        }
        try {
            $bot->handleUpdate((array) $request->json()->all());
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['ok' => true]);
    }
}
