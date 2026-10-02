<?php

namespace App\Http\Middleware;

use App\Services\ProductShots\ProductShotFeature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * فیچر فلگ «استودیو محصول». با فلگ خاموش (یا کاربر خارج از مخاطب) ۴۰۴ برمی‌گرداند
 * تا هیچ اثری از قابلیت جدید دیده نشود. حالت admin فقط روشن‌بودن ماژول را می‌سنجد.
 */
class EnsureProductShotsAvailable
{
    public function __construct(private ProductShotFeature $feature) {}

    public function handle(Request $request, Closure $next, string $mode = 'user'): Response
    {
        $allowed = $mode === 'admin'
            ? $this->feature->enabled()
            : $this->feature->availableFor($request->user());

        if (! $allowed) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'پیدا نشد.', 'error_code' => 'NOT_FOUND'], 404);
            }
            abort(404);
        }

        return $next($request);
    }
}
