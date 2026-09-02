<?php

namespace App\Http\Controllers;

use App\Models\TelegramEvent;
use App\Services\TelegramFlowService;
use App\Services\TelegramInteractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private readonly TelegramFlowService $flow,
        private readonly TelegramInteractionService $interaction,
    )
    {
    }

    public function update(Request $request): JsonResponse
    {
        $expected = trim((string) config('services.telegram.webhook_secret'));
        $received = (string) $request->header('X-Telegram-Bot-Api-Secret-Token');
        if ($expected === '') {
            return response()->json(['ok' => false, 'message' => 'کلید امن وب‌هوک تنظیم نشده است.'], 503);
        }
        if ($received === '' || ! hash_equals($expected, $received)) {
            return response()->json(['ok' => false, 'message' => 'درخواست وب‌هوک معتبر نیست.'], 403);
        }

        $payload = $request->all();
        $updateId = isset($payload['update_id']) ? (int) $payload['update_id'] : null;
        if ($updateId !== null && TelegramEvent::query()->where('update_id', $updateId)->exists()) {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        try {
            $normalized = $this->normalize($payload);
            $this->interaction->answerCallbackQuery($normalized['callback_query_id'] ?? null);
            $response = $this->flow->handle($normalized);
            $this->interaction->deleteMessage($normalized['chat_id'] ?? null, $normalized['message_id'] ?? null);

            return response()->json($response);
        } catch (ValidationException $exception) {
            return response()->json([
                'ok' => false,
                'message' => collect($exception->errors())->flatten()->first() ?: 'اطلاعات ارسالی معتبر نیست.',
                'errors' => $exception->errors(),
            ], 422);
        } catch (\Throwable $exception) {
            Log::error('Telegram webhook processing failed.', [
                'update_id' => $updateId,
                'exception' => $exception,
            ]);
            return response()->json(['ok' => false, 'message' => 'پردازش رویداد تلگرام انجام نشد.'], 500);
        }
    }

    private function normalize(array $payload): array
    {
        if (isset($payload['telegram'])) {
            return $payload;
        }

        $message = (array) ($payload['message'] ?? $payload['edited_message'] ?? []);
        $callback = (array) ($payload['callback_query'] ?? []);
        $from = (array) ($message['from'] ?? $callback['from'] ?? []);
        $chat = (array) ($message['chat'] ?? data_get($callback, 'message.chat', []));
        $text = (string) ($message['text'] ?? '');
        $contact = (array) ($message['contact'] ?? []);
        $callbackData = (string) ($callback['data'] ?? '');
        $startPayload = null;
        if (preg_match('/^\/start(?:\s+([^\s]+))?/u', $text, $matches)) {
            $startPayload = $matches[1] ?? null;
        }

        return array_filter([
            'update_id' => $payload['update_id'] ?? null,
            'telegram' => $from,
            'chat_id' => $chat['id'] ?? $from['id'] ?? null,
            'text' => $text,
            'start_payload' => $startPayload,
            'callback_data' => $callbackData ?: null,
            'callback_query_id' => $callback['id'] ?? null,
            'message_id' => $message['message_id'] ?? data_get($callback, 'message.message_id'),
            'contact' => $contact ?: null,
            'payload' => $payload,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
