<?php

namespace App\Http\Controllers;

use App\Models\TelegramProductEvent;
use App\Services\TelegramProduct\TelegramProductBotRenderer;
use App\Services\TelegramProductDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * وبهوک مستقیم تلگرام برای بات ثبت محصول (بدون n8n).
 *
 * آپدیت خام تلگرام را به همان ورودی نرمال‌شده‌ی n8n تبدیل می‌کند، به همان
 * TelegramProductDraftService می‌دهد و پاسخ را خودش در تلگرام نمایش می‌دهد.
 * تا وقتی وبهوک بات با فرمان telegram:product-bot-webhook به این آدرس منتقل نشود،
 * این مسیر هیچ ترافیکی ندارد و بات روی n8n می‌ماند.
 */
class TelegramProductDirectWebhookController extends Controller
{
    public function __construct(
        private readonly TelegramProductDraftService $drafts,
        private readonly TelegramProductBotRenderer $renderer,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $expected = self::secret();
        $received = (string) $request->header('X-Telegram-Bot-Api-Secret-Token');
        if ($expected === '' || $received === '' || ! hash_equals($expected, $received)) {
            return response()->json(['ok' => false], 403);
        }

        $input = self::normalize((array) $request->json()->all());
        if ($input === null) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }
        $context = [
            'chat_id' => $input['chat_id'],
            'event' => $input['event'],
            'callback_query_id' => $input['callback_query_id'] ?? null,
            'callback_message_id' => $input['event'] === 'callback' ? ($input['message_id'] ?? null) : null,
        ];

        // تلگرام در صورت تأخیر همان آپدیت را دوباره می‌فرستد؛ دوباره نمایش نده.
        if (TelegramProductEvent::query()->where('update_id', $input['update_id'])->exists()) {
            $this->renderer->deliver(['chat_id' => $input['chat_id'], 'text' => '', 'duplicate_update' => true], $context);

            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        try {
            $response = $this->drafts->handle($input);
        } catch (ValidationException $exception) {
            $response = ['chat_id' => $input['chat_id'], 'text' => '⚠️ ' . collect($exception->errors())->flatten()->first(), 'status' => 'error'];
        } catch (\Throwable $exception) {
            Log::error('telegram product direct webhook failed', ['update_id' => $input['update_id'], 'error' => $exception->getMessage()]);
            $response = ['chat_id' => $input['chat_id'], 'text' => '⚠️ خطای غیرمنتظره‌ای رخ داد؛ چند لحظه بعد دوباره امتحان کنید. اگر تکرار شد، «لغو فرآیند» و شروع دوباره را بزنید.', 'status' => 'error'];
        }

        $this->renderer->deliver($response, $context);

        return response()->json(['ok' => true]);
    }

    public static function secret(): string
    {
        return trim((string) (config('services.telegram_product.direct_webhook_secret') ?: config('services.telegram_product.webhook_secret')));
    }

    /**
     * آپدیت تلگرام → ورودی نرمال‌شده (همان کلیدهایی که n8n می‌فرستاد).
     * فقط چت خصوصی؛ گروه‌ها و کانال‌ها نادیده گرفته می‌شوند.
     */
    public static function normalize(array $update): ?array
    {
        $updateId = isset($update['update_id']) ? (int) $update['update_id'] : null;
        $callback = $update['callback_query'] ?? null;
        $message = $callback['message'] ?? $update['message'] ?? null;
        if ($updateId === null || ! is_array($message)) {
            return null;
        }
        if (($message['chat']['type'] ?? 'private') !== 'private') {
            return null;
        }
        $from = (array) ($callback['from'] ?? $message['from'] ?? []);
        $input = [
            'update_id' => $updateId,
            'transport' => 'direct',
            'chat_id' => (string) ($message['chat']['id'] ?? $from['id'] ?? ''),
            'message_id' => isset($message['message_id']) ? (string) $message['message_id'] : null,
            'telegram' => array_filter([
                'id' => isset($from['id']) ? (int) $from['id'] : null,
                'first_name' => $from['first_name'] ?? null,
                'last_name' => $from['last_name'] ?? null,
                'username' => $from['username'] ?? null,
                'language_code' => $from['language_code'] ?? null,
            ], fn ($v) => $v !== null),
        ];
        if (empty($input['telegram']['id']) || $input['chat_id'] === '') {
            return null;
        }

        if (is_array($callback)) {
            $input['event'] = 'callback';
            $input['callback_data'] = (string) ($callback['data'] ?? '');
            $input['callback_query_id'] = (string) ($callback['id'] ?? '');

            return $input['callback_data'] === '' ? null : $input;
        }

        $fileId = null;
        if (! empty($message['photo']) && is_array($message['photo'])) {
            $sizes = $message['photo'];
            usort($sizes, fn ($a, $b) => ((int) ($a['file_size'] ?? 0) + (int) ($a['width'] ?? 0)) <=> ((int) ($b['file_size'] ?? 0) + (int) ($b['width'] ?? 0)));
            $fileId = (string) (end($sizes)['file_id'] ?? '');
        } elseif (str_starts_with(strtolower((string) ($message['document']['mime_type'] ?? '')), 'image/')) {
            $fileId = (string) ($message['document']['file_id'] ?? '');
        }
        if ($fileId) {
            $input['event'] = 'photo';
            $input['file_id'] = $fileId;
            $input['media_group_id'] = $message['media_group_id'] ?? null;

            return $input;
        }

        $text = trim((string) ($message['text'] ?? $message['caption'] ?? ''));
        if ($text === '') {
            return null;
        }
        $input['text'] = $text;
        $input['event'] = str_starts_with($text, '/start') ? 'start' : 'message';
        if (str_starts_with($text, '/start')) {
            $input['text'] = '/start';
        }

        return $input;
    }
}

