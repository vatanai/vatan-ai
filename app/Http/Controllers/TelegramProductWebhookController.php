<?php

namespace App\Http\Controllers;

use App\Models\TelegramProductDraft;
use App\Services\TelegramProductDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramProductWebhookController extends Controller
{
    public function __construct(private readonly TelegramProductDraftService $drafts)
    {
    }

    public function update(Request $request): JsonResponse
    {
        if ($error = $this->authorizationError($request)) {
            return $error;
        }
        $telegram = $request->input('telegram', []);
        if (is_string($telegram)) {
            $telegram = json_decode($telegram, true) ?: [];
        }
        $input = array_merge($request->except(['image', 'file', 'telegram']), [
            'telegram' => is_array($telegram) ? $telegram : [],
        ]);
        $input['telegram_id'] = $input['telegram_id'] ?? data_get($input, 'telegram.id');
        $response = $this->drafts->handle($input, $request->file('image') ?: $request->file('file'));
        return response()->json($response);
    }

    public function status(Request $request, TelegramProductDraft $draft): JsonResponse
    {
        if ($error = $this->authorizationError($request)) {
            return $error;
        }
        return response()->json($this->drafts->status($draft));
    }

    private function authorizationError(Request $request): ?JsonResponse
    {
        $expected = trim((string) config('services.telegram_product.webhook_secret'));
        $received = (string) $request->header('X-Vatan-Product-Webhook-Secret');
        if ($expected === '') {
            return response()->json(['ok' => false, 'message' => 'کلید امن وب‌هوک ثبت محصول تنظیم نشده است.'], 503);
        }
        if ($received === '' || ! hash_equals($expected, $received)) {
            return response()->json(['ok' => false, 'message' => 'درخواست وب‌هوک ثبت محصول معتبر نیست.'], 403);
        }
        return null;
    }
}
