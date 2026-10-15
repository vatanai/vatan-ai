<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductShots\ProductShotFeature;
use App\Services\ProductShots\ShotPackService;
use Illuminate\Http\Request;

/**
 * صفحات اصلی پک شات محصول پروداکتی:
 *   /app/pro/{کد-کوتاه}     صفحه‌ی محصول
 *   /app/crepro/{کد-کوتاه}  صفحه‌ی ساخت
 * دسترسی همان قاعده‌ی فیچر «استودیو محصول» است (ProductShotFeature::availableFor).
 */
class ProductShotPageController extends Controller
{
    public function __construct(private ShotPackService $packs)
    {
    }

    public function product(Request $request, Product $product)
    {
        $this->guard($request, $product);

        $shots = $this->packs->shotCards($product);
        $defaultShots = array_values(array_filter($shots, fn (array $s) => ! empty($s['is_default'])));
        if (! $defaultShots) {
            $defaultShots = array_slice($shots, 0, 4);
        }

        $gallery = collect($shots)
            ->filter(fn (array $s) => ! empty($s['sample_url']))
            ->map(fn (array $s) => ['url' => $s['sample_url'], 'label' => $s['name']])
            ->values();
        if ($gallery->isEmpty()) {
            $gallery = collect($this->packs->collage($product, 6))
                ->map(fn (string $url) => ['url' => $url, 'label' => $product->name_fa ?: $product->name_en]);
        }

        $minCredit = collect($shots)
            ->flatMap(fn (array $s) => array_values((array) ($s['quality_credits'] ?? [])) ?: [(int) ($s['credits'] ?? 0)])
            ->filter(fn ($c) => (int) $c > 0)
            ->min();

        $similar = Product::query()
            ->where('status', 'active')
            ->where('product_mode', Product::MODE_PRODUCT)
            ->whereNotNull('product_code')
            ->where('id', '!=', $product->id)
            ->latest()
            ->limit(10)
            ->get();

        $user = $request->user();
        $isSaved = false;
        $isLiked = false;
        if ($user) {
            try { $isSaved = $user->hasSavedProduct($product->id); } catch (\Throwable) {}
            try { $isLiked = $user->hasLikedProduct($product->id); } catch (\Throwable) {}
        }

        return view('app.productshot-v2.product', [
            'product' => $product,
            'shots' => $shots,
            'defaultCount' => count($defaultShots),
            'gallery' => $gallery,
            'minCredit' => (int) ($minCredit ?? 0),
            'similar' => $similar,
            'isSaved' => $isSaved,
            'isLiked' => $isLiked,
            'createUrl' => route('app.crepro', ['product' => $product->short_code]),
        ]);
    }

    public function create(Request $request, Product $product)
    {
        $this->guard($request, $product);

        // همان داده‌ها و APIهای ساخت پک؛ فقط ظاهر صفحه عوض شده است.
        $data = app(ProductShotController::class)->page($product)->getData();
        $data['productUrl'] = route('app.pro', ['product' => $product->short_code]);

        return view('app.productshot-v2.create', $data);
    }

    private function guard(Request $request, Product $product): void
    {
        abort_unless($product->status === 'active' && $product->isShotProduct() && $product->short_code, 404);
        abort_unless(
            app()->environment('local') || app(ProductShotFeature::class)->availableFor($request->user()),
            404,
        );
    }
}
