<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * کنترلر Webhook Instagram برای DM و کامنت‌ها
 *
 * شامل:
 * • Verification Token Check
 * • دریافت DM‌ها و کامنت‌ها
 * • ذخیره در Database
 * • فوری پردازش برای Response
 */
class InstagramWebhookController extends Controller
{
    /**
     * تأیید Webhook (GET request از Meta)
     *
     * Meta فقط وقتی دیتا می‌فرسته که این endpoint موجود باشد
     */
    public function verify(Request $request): JsonResponse
    {
        $verifyToken = env('INSTAGRAM_WEBHOOK_TOKEN', 'vatan-instagram-webhook-secret-2026');
        $challenge = $request->input('hub_challenge');
        $token = $request->input('hub_verify_token');

        Log::info('[Instagram Webhook] Verification attempt', [
            'token_provided' => substr($token ?? '', 0, 10) . '...',
            'challenge' => $challenge ? 'yes' : 'no',
        ]);

        if ($token === $verifyToken) {
            return response()->json(['hub_challenge' => $challenge], 200);
        }

        Log::warning('[Instagram Webhook] Verification failed - invalid token');
        return response()->json(['error' => 'Invalid verification token'], 403);
    }

    /**
     * دریافت رویدادهای Instagram (DM‌ها و کامنت‌ها)
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $data = $request->input('entry', []);

            if (empty($data)) {
                Log::warning('[Instagram Webhook] Empty entry data received');
                return response()->json(['status' => 'received'], 200);
            }

            foreach ($data as $entry) {
                // دریافت‌های Messaging (DM‌ها)
                $messaging = $entry['messaging'] ?? [];
                foreach ($messaging as $event) {
                    $this->processMessage($event);
                }

                // تغییرات (کامنت‌ها)
                $changes = $entry['changes'] ?? [];
                foreach ($changes as $change) {
                    $this->processChange($change);
                }
            }

            return response()->json(['status' => 'received'], 200);
        } catch (\Exception $e) {
            Log::error('[Instagram Webhook] Error processing event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * پردازش DM‌های Instagram
     */
    private function processMessage(array $event): void
    {
        try {
            $senderId = $event['sender']['id'] ?? null;
            $message = $event['message'] ?? [];
            $timestamp = $event['timestamp'] ?? now()->timestamp;

            if (!$senderId || empty($message)) {
                return;
            }

            $messageText = $message['text'] ?? '';
            $messageId = $message['mid'] ?? null;

            Log::info('[Instagram DM] Received', [
                'sender_id' => $senderId,
                'message_id' => $messageId,
                'text_length' => strlen($messageText),
            ]);

            // جزئیات Contact و Message ذخیره کریں
            // این بخش Phase 2 میں بسط پیدا می‌شود
            DB::table('instagram_messages')->insertOrIgnore([
                'contact_id' => $senderId, // موقتی - بعداً Contact ID
                'direction' => 'inbound',
                'source_type' => 'dm',
                'body' => $messageText,
                'sent_by' => 'user',
                'occurred_at' => now(),
                'created_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('[Instagram DM] Processing error', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * پردازش تغییرات (کامنت‌ها)
     */
    private function processChange(array $change): void
    {
        try {
            $field = $change['field'] ?? null;

            if ($field === 'comments') {
                $value = $change['value'] ?? [];
                Log::info('[Instagram Comment] Received', [
                    'media_id' => $value['media_id'] ?? null,
                    'from' => $value['from']['username'] ?? 'unknown',
                ]);
                // Comment processing در Phase 1
            }
        } catch (\Exception $e) {
            Log::error('[Instagram Change] Processing error', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
