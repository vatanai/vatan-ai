<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductShots\ShotPackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/** پیش‌نمایش محلی صفحات جدید پروداکتی؛ مستقل از مسیرهای اصلی محصول است. */
class ProductShotPreviewController extends Controller
{
    public function __construct(private ShotPackService $packs)
    {
    }

    /** فقط لوکال: بازسازی محصولات دمو. */
    public function seedDemo()
    {
        abort_unless(app()->environment('local'), 404);
        Artisan::call('db:seed', ['--class' => 'ProductShotDemoSeeder', '--force' => true]);

        return redirect()->route('app.ps-v2')->with('success', trim(Artisan::output()));
    }

    /** فهرست محصولات پروداکتی دمو برای بازبینی طراحی. */
    public function index()
    {
        abort_unless(app()->environment('local'), 404);

        $products = Product::query()
            ->where('product_mode', Product::MODE_PRODUCT)
            ->where('slug', 'like', 'demo-ps-%')
            ->latest()
            ->get(['id', 'slug', 'product_code', 'name_fa', 'name_en', 'status', 'created_at']);

        return response()
            ->view('app.productshot-v2.index', compact('products'))
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function show(Request $request, Product $product)
    {
        $this->guard($product);

        $shots = $this->packs->shotCards($product);
        $defaultShots = array_values(array_filter($shots, fn (array $s) => ! empty($s['is_default'])));
        if (! $defaultShots) {
            $defaultShots = array_slice($shots, 0, 4);
        }

        $gallery = $this->packs->productGallery($product, $shots);
        $inputImage = $this->packs->inputImageUrl($product);

        $minCredit = collect($shots)
            ->flatMap(fn (array $s) => array_values((array) ($s['quality_credits'] ?? [])) ?: [(int) ($s['credits'] ?? 0)])
            ->filter(fn ($c) => (int) $c > 0)
            ->min();

        $similar = Product::query()
            ->where('status', 'active')
            ->where('product_mode', Product::MODE_PRODUCT)
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

        return response()->view('app.productshot-v2.product', [
            'product' => $product,
            'shots' => $shots,
            'defaultCount' => count($defaultShots),
            'defaultCredits' => array_sum(array_map(fn (array $s) => (int) ($s['credits'] ?? 0), $defaultShots)),
            'gallery' => $gallery,
            'inputImage' => $inputImage,
            'minCredit' => (int) ($minCredit ?? 0),
            'qualityCount' => count((array) config('product_shots.quality_levels', [])),
            'similar' => $similar,
            'isSaved' => $isSaved,
            'isLiked' => $isLiked,
            'createUrl' => route('app.ps-v2.crepro', $product->route_slug),
        ])->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function create(Request $request, Product $product)
    {
        $this->guard($product);

        $data = app(ProductShotController::class)->page($product)->getData();
        $data['productUrl'] = route('app.ps-v2.pro', $product->route_slug);

        return response()->view('app.productshot-v2.create', $data)->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function guard(Product $product): void
    {
        abort_unless(app()->environment('local'), 404);
        abort_unless($product->status === 'active' && $product->isShotProduct(), 404);
    }
}
