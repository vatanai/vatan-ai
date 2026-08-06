<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductCatalogController extends Controller
{
    public function index(Request $request)
    {
        return $this->renderCatalog($request);
    }

    public function category(Request $request, ?string $path = null)
    {
        if (!$path) {
            return $this->renderCatalog($request);
        }

        $category = Category::active()->where(function (Builder $query) use ($path) {
            $query->where('path', $path)->orWhere('slug', $path);
        })->firstOrFail();

        $request->merge(['categories' => [$category->id]]);

        return $this->renderCatalog($request, $category);
    }

    private function renderCatalog(Request $request, ?Category $pageCategory = null)
    {
        $selectedCategories = collect((array) $request->input('categories', []))
            ->filter(fn ($id) => ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $query = Product::query()
            ->with('categories:id,name,name_fa,slug,path')
            ->where('status', 'active');

        $targetProductIds = collect(explode(',', (string) $request->input('product_ids')))
            ->filter(fn ($id) => ctype_digit($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        if ($targetProductIds) {
            $query->whereIn('id', $targetProductIds);
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->boolean('video')) {
            $query->whereIn('media_type', ['video', 'both']);
        }

        if ($request->filled('legacy_category')) {
            $query->where('category', $request->input('legacy_category'));
        }

        if ($request->filled('legacy_subcategory')) {
            $query->where('subcategory', $request->input('legacy_subcategory'));
        }

        if ($search = trim((string) $request->input('search'))) {
            $terms = $this->expandedSearchTerms($search);
            $matchedCategoryIds = Category::query()->active()->where(function (Builder $builder) use ($terms) {
                foreach ($terms as $word) {
                    $builder->orWhere('name_fa', 'like', "%{$word}%")->orWhere('name_en', 'like', "%{$word}%");
                }
            })->pluck('id');

            $query->where(function (Builder $builder) use ($terms, $matchedCategoryIds) {
                foreach ($terms as $word) {
                    $builder->orWhere('name_fa', 'like', "%{$word}%")
                        ->orWhere('name_en', 'like', "%{$word}%")
                        ->orWhere('description_fa', 'like', "%{$word}%")
                        ->orWhere('description_en', 'like', "%{$word}%")
                        ->orWhere('category', 'like', "%{$word}%")
                        ->orWhere('subcategory', 'like', "%{$word}%")
                        ->orWhere('tags', 'like', "%{$word}%");
                }
                if ($matchedCategoryIds->isNotEmpty()) {
                    $builder->orWhereIn('category_id', $matchedCategoryIds)
                        ->orWhereHas('categories', fn (Builder $relation) => $relation->whereIn('categories.id', $matchedCategoryIds));
                }
            });
        }

        if ($selectedCategories) {
            $query->where(function (Builder $builder) use ($selectedCategories) {
                $builder->whereIn('category_id', $selectedCategories)
                    ->orWhereHas('categories', fn (Builder $relation) => $relation->whereIn('categories.id', $selectedCategories));
            });
        }

        if ($request->filled('media_type')) {
            $query->where('media_type', $request->input('media_type'));
        }

        if ($request->input('pricing') === 'free') {
            $query->where('credit_cost', 0);
        } elseif ($request->input('pricing') === 'paid') {
            $query->where('credit_cost', '>', 0);
        }

        match ($request->input('sort')) {
            'oldest' => $query->oldest(),
            'name' => $query->orderBy('name_fa'),
            'credit_low' => $query->orderBy('credit_cost'),
            default => $query->latest(),
        };

        $products = $query->paginate(18)->withQueryString();
        $categories = Category::active()->orderBy('sort_order')->orderBy('name_fa')->get();
        $categories->each(function (Category $category) {
            $category->setAttribute('products_count', Product::query()
                ->where('status', 'active')
                ->where(function (Builder $query) use ($category) {
                    $query->where('category_id', $category->id)
                        ->orWhereHas('categories', fn (Builder $relation) => $relation->where('categories.id', $category->id));
                })->count());
        });
        $categories = $categories->filter(fn (Category $category) => (int) $category->products_count > 0)->values();

        return view('app.products.index', compact('products', 'categories', 'selectedCategories', 'pageCategory'));
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
