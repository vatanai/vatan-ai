<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\TelegramProductClick;
use App\Services\TelegramIdentityService;
use App\Services\TelegramInitDataValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TelegramMiniAppController extends Controller
{
    public function __construct(
        private readonly TelegramInitDataValidator $validator,
        private readonly TelegramIdentityService $identity,
    ) {
    }

    public function show(Request $request): View
    {
        $launchToken = trim((string) $request->query('launch', '')) ?: null;
        $all = $request->boolean('all');
        $click = $launchToken ? TelegramProductClick::query()->with('product')->where('launch_token', $launchToken)->first() : null;
        $product = $click?->product;
        $fallbackUrl = $all || ! $product
            ? route('app.home')
            : route('app.create', ['product' => $product->route_slug]);

        return view('telegram.mini-app', [
            'launchToken' => $launchToken,
            'allProducts' => $all,
            'fallbackUrl' => $fallbackUrl,
        ]);
    }

    public function session(Request $request): JsonResponse
    {
        $initData = (string) ($request->input('init_data') ?: $request->header('X-Telegram-Init-Data', ''));
        try {
            $validated = $this->validator->validate($initData);
            $telegramUser = $this->identity->upsert(
                (array) $validated['user'],
                null,
                'mini_app_open',
                ['query_id' => $validated['query_id'], 'auth_date' => $validated['auth_date']],
                (string) $validated['user']['id'],
            );

            if (! $telegramUser->user_id || ! $telegramUser->user) {
                return response()->json([
                    'ok' => false,
                    'code' => 'TELEGRAM_REGISTRATION_REQUIRED',
                    'message' => 'ابتدا ثبت‌نام را در بات کامل کنید.',
                ], 409);
            }

            $launchToken = trim((string) $request->input('launch_token')) ?: null;
            $all = $request->boolean('all');
            $click = $launchToken
                ? TelegramProductClick::query()
                    ->with('product')
                    ->where('launch_token', $launchToken)
                    ->where('telegram_user_id', $telegramUser->id)
                    ->where('clicked_at', '>=', now()->subDay())
                    ->first()
                : null;

            if ($launchToken && ! $click) {
                return response()->json(['ok' => false, 'code' => 'TELEGRAM_LAUNCH_EXPIRED', 'message' => 'لینک ساخت محصول منقضی شده است.'], 422);
            }

            Auth::login($telegramUser->user, true);
            $request->session()->regenerate();
            if ($click) $click->forceFill(['opened_at' => now()])->save();

            $redirect = $all || ! $click?->product
                ? route('app.home')
                : route('app.create', ['product' => $click->product->route_slug]);

            return response()->json([
                'ok' => true,
                'redirect' => $redirect,
                'user' => [
                    'id' => $telegramUser->user->id,
                    'name' => trim($telegramUser->user->name . ' ' . $telegramUser->user->last_name),
                    'tokens' => (int) $telegramUser->user->fresh()->tokens,
                ],
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'ok' => false,
                'code' => 'TELEGRAM_INIT_DATA_INVALID',
                'message' => collect($exception->errors())->flatten()->first() ?: 'ورود تلگرام معتبر نیست.',
            ], 422);
        } catch (\Throwable $exception) {
            Log::error('Telegram Mini App session failed.', ['exception' => $exception]);
            return response()->json(['ok' => false, 'message' => 'ورود به Mini App انجام نشد.'], 500);
        }
    }
}
