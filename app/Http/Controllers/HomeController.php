<?php

namespace App\Http\Controllers;

use App\Models\HomeSection;
use App\Models\Category;
use App\Models\Product;
use App\Services\HomeBuilder\HomeSectionRenderService;
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

        $sections = HomeSection::forPage($pageKey)->published()->ordered()->get();

        $renderedSections = $this->renderService->prepareMany($sections);

        return view('app.home', compact('renderedSections'));
    }

    /**
     * جستجوی زنده هوم؛ فقط محصولات فعال و فیلدهای لازم رابط کاربری را برمی‌گرداند.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
        ]);
        $term = trim($validated['q']);
        $terms = $this->expandedSearchTerms($term);
        $categoryIds = Category::query()->active()->where(function (Builder $query) use ($terms) {
            foreach ($terms as $word) {
                $query->orWhere('name_fa', 'like', "%{$word}%")
                    ->orWhere('name_en', 'like', "%{$word}%");
            }
        })->pluck('id');

        $products = Product::query()
            ->where('status', 'active')
            ->where(function (Builder $query) use ($terms, $categoryIds) {
                foreach ($terms as $word) {
                    $query->orWhere('name_fa', 'like', "%{$word}%")
                        ->orWhere('name_en', 'like', "%{$word}%")
                        ->orWhere('description_fa', 'like', "%{$word}%")
                        ->orWhere('description_en', 'like', "%{$word}%")
                        ->orWhere('category', 'like', "%{$word}%")
                        ->orWhere('subcategory', 'like', "%{$word}%")
                        ->orWhere('tags', 'like', "%{$word}%");
                }
                if ($categoryIds->isNotEmpty()) {
                    $query->orWhereIn('category_id', $categoryIds)
                        ->orWhereHas('categories', fn (Builder $relation) => $relation->whereIn('categories.id', $categoryIds));
                }
            })
            ->latest()
            ->limit(8)
            ->get();

        if ($products->count() < 8) {
            $similarCategoryIds = $categoryIds->concat($products->pluck('category_id'))->filter()->unique()->values();
            $similar = Product::query()->where('status', 'active')->whereNotIn('id', $products->pluck('id'))
                ->when($similarCategoryIds->isNotEmpty(), function (Builder $query) use ($similarCategoryIds) {
                    $query->where(function (Builder $builder) use ($similarCategoryIds) {
                        $builder->whereIn('category_id', $similarCategoryIds)
                            ->orWhereHas('categories', fn (Builder $relation) => $relation->whereIn('categories.id', $similarCategoryIds));
                    });
                })
                ->orderByDesc('is_featured')->latest()->limit(8 - $products->count())->get();
            $products = $products->concat($similar)->unique('id')->values();
        }

        $products = $products
            ->map(fn (Product $product) => [
                'name' => $product->name_fa,
                'meta' => trim(collect([$product->category, $product->subcategory])->filter()->join(' · ')),
                'image' => $product->displayImageUrl(),
                'url' => route('app.product', $product->route_slug),
            ]);

        return response()->json([
            'items' => $products,
            'all_results_url' => route('products.index', ['search' => $term]),
        ]);
    }

    private function expandedSearchTerms(string $term): array
    {
        $words = collect(preg_split('/\s+/u', $term))->map(fn ($word) => trim($word))->filter(fn ($word) => mb_strlen($word) >= 2);
        $synonyms = [
            'مادر' => ['خانواده', 'خانوادگی'], 'پدر' => ['خانواده', 'خانوادگی'], 'فرزند' => ['کودک', 'خانواده'],
            'عروسی' => ['ازدواج', 'دعوت'], 'ازدواج' => ['عروسی', 'دعوت'], 'پروفایل' => ['پرتره', 'آواتار'],
            'اینستا' => ['اینستاگرام', 'ریلز', 'استوری'], 'باشگاه' => ['ورزشی', 'تبلیغاتی'],
        ];
        foreach ($words->all() as $word) {
            $words = $words->concat($synonyms[$word] ?? []);
        }

        return $words->unique()->values()->all() ?: [$term];
    }
}
