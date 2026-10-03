<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * کنترلر Webhook Telegram برای دریافت پیام‌های گروپ
 *
 * شامل:
 * • دریافت پیام‌های گروپ/تاپیک
 * • Commands: /start, /portfolio, /pricing, /demo, /help
 * • Sync message back to dashboard/instagram
 */
class TelegramWebhookController extends Controller
{
    /**
     * دریافت رویدادهای Telegram
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $update = $request->all();

            if (empty($update)) {
                Log::warning('[Telegram Webhook] Empty update received');
                return response()->json(['ok' => true], 200);
            }

            $updateId = $update['update_id'] ?? null;
            Log::info('[Telegram Webhook] Update received', ['update_id' => $updateId]);

            // بررسی Command
            if (isset($update['message'])) {
                $message = $update['message'];
                $text = $message['text'] ?? '';
                $chatId = $message['chat']['id'] ?? null;
                $userId = $message['from']['id'] ?? null;

                // Commands
                if (strpos($text, '/') === 0) {
                    $this->handleCommand($text, $chatId, $userId);
                    return response()->json(['ok' => true], 200);
                }

                // عادی پیام
                $this->processMessage($message);
            }

            return response()->json(['ok' => true], 200);
        } catch (\Exception $e) {
            Log::error('[Telegram Webhook] Error', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['ok' => false], 500);
        }
    }

    /**
     * پردازش Commands
     */
    private function handleCommand(string $command, $chatId, $userId): void
    {
        $cmd = explode(' ', $command)[0];

        Log::info('[Telegram Command]', [
            'command' => $cmd,
            'chat_id' => $chatId,
            'user_id' => $userId,
        ]);

        // Commands:
        // /start - شروع و منو
        // /portfolio - نمونه کارها
        // /pricing - قیمت‌گذاری
        // /demo - درخواست نمایش
        // /help - راهنما

        // این بخش در Phase 3 بسط پیدا می‌شود
    }

    /**
     * پردازش عادی پیام‌های تاپیک
     */
    private function processMessage(array $message): void
    {
        try {
            $messageId = $message['message_id'] ?? null;
            $chatId = $message['chat']['id'] ?? null;
            $userId = $message['from']['id'] ?? null;
            $text = $message['text'] ?? '';
            $timestamp = $message['date'] ?? now()->timestamp;

            // اگر در تاپیک است
            $topicId = $message['message_thread_id'] ?? null;

            Log::info('[Telegram Message] Received', [
                'message_id' => $messageId,
                'topic_id' => $topicId,
                'user_id' => $userId,
                'text_length' => strlen($text),
            ]);

            // پیدا کردن Contact از Telegram User ID
            $contact = DB::table('instagram_contacts')
                ->where('telegram_user_id', $userId)
                ->first();

            if ($contact) {
                // ذخیره پیام
                DB::table('instagram_messages')->insert([
                    'contact_id' => $contact->id,
                    'direction' => 'inbound',
                    'source_type' => 'telegram',
                    'body' => $text,
                    'sent_by' => 'user',
                    'telegram_message_id' => $messageId,
                    'occurred_at' => now(),
                    'created_at' => now(),
                ]);

                Log::info('[Telegram Message] Linked to contact', [
                    'contact_id' => $contact->id,
                ]);
            } else {
                Log::warning('[Telegram Message] Contact not found', [
                    'telegram_user_id' => $userId,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('[Telegram Message] Processing error', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
