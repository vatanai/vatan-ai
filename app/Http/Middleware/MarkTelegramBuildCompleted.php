<?php

namespace App\Http\Middleware;

use App\Models\Product;
use App\Models\TelegramProductClick;
use App\Models\TelegramUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarkTelegramBuildCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response->isSuccessful()) {
            return $response;
        }

        $telegramUserId = (int) $request->session()->get('telegram_mini_app_user_id');
        $launchToken = trim((string) $request->session()->get('telegram_mini_app_launch_token'));
        $product = $request->route('product');

        if ($telegramUserId < 1 || $launchToken === '' || ! $product) {
            return $response;
        }

        $productId = $product instanceof Product
            ? (int) $product->getKey()
            : (int) Product::query()->where('route_slug', (string) $product)->orWhere('slug', (string) $product)->value('id');
        if ($productId < 1) {
            return $response;
        }

        $telegramUser = TelegramUser::query()->find($telegramUserId);
        if (! $telegramUser) {
            return $response;
        }

        $telegramUser->productClicks()
            ->where('launch_token', $launchToken)
            ->where('product_id', $productId)
            ->whereNull('completed_at')
            ->update(['completed_at' => now()]);

        return $response;
    }
}
