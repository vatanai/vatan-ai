<?php

namespace App\Http\Controllers;

use App\Models\HomeSection;
use App\Models\Product;
use App\Services\HomeBuilder\HomeSectionRenderService;
use App\Services\ProductSearchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(protected HomeSectionRenderService $renderService)
    {
    }

    /**
     * نمایش صفحه اصلی اپ.
     * Sectionهای این صفحه دیگر در کد ثابت نیستند — از پنل مدیریت («مدیریت صفحه هوم»، فیچر Home Builder)
     * به‌صورت داینامیک مدیریت می‌شوند. فقط Sectionهای published و به‌ترتیب position واکشی می‌شوند.
     */
    public function index()
    {
        $pageKey = config('home_builder.default_page_key', HomeSection::DEFAULT_PAGE_KEY);

        $sections = HomeSection::forPage($pageKey)
            ->published()
            ->ordered()
            ->get();

        $renderedSections = $this->renderService->prepareMany($sections);

        return view('app.home', compact('renderedSections'));
    }

    /**
     * جستجوی زنده هوم؛ فقط محصولات فعال و فیلدهای لازم رابط کاربری را برمی‌گرداند.
     */
    public function search(Request $request, ProductSearchService $productSearch): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
        ]);
        $term = trim($validated['q']);

        $products = Product::query()
            ->where('status', 'active')
            ->tap(fn (Builder $query) => $productSearch->apply($query, $term))
            ->latest()
            ->limit(8)
            ->get();

        $products = $products
            ->map(fn (Product $product) => [
                'name' => $product->name_fa,
                'meta' => trim(collect([$product->category, $product->subcategory])->filter()->join(' · ')),
                'image' => $product->displayImageUrl(),
                'url' => route('app.product', $product->route_slug),
            ]);

        // مسیر محتوایی افزایش کیفیت باید برای جست‌وجوهای مرتبط همیشه نتیجه‌ی اول باشد.
        $normalizedTerm = mb_strtolower($term, 'UTF-8');
        $isQualityIntent = str_contains($normalizedTerm, 'کیفیت')
            || str_contains($normalizedTerm, 'upscale')
            || str_contains($normalizedTerm, 'enhance');
        if ($isQualityIntent) {
            $products->prepend([
                'name' => 'افزایش کیفیت عکس با هوش مصنوعی',
                'meta' => 'راهنمای کامل + ۴ محصول آماده',
                'image' => asset('assets/img/ai-photo-editor-prompt.webp'),
                'url' => route('landing.image-quality'),
                'kind' => 'landing',
            ]);
        }

        return response()->json([
            'items' => $products,
            'all_results_url' => route('products.index', ['search' => $term]),
        ]);
    }

}
