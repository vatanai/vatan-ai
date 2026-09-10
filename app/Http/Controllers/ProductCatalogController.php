<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductSearchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->select(['id', 'name_fa', 'description_fa', 'media_type', 'credit_cost', 'slug', 'product_code', 'cover', 'sample_outputs', 'thumbnail'])
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
            app(ProductSearchService::class)->apply($query, $search);
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
        $directProducts = DB::table('products')
            ->where('status', 'active')
            ->whereNotNull('category_id')
            ->selectRaw('category_id, id as product_id');
        $linkedProducts = DB::table('category_product as cp')
            ->join('products as p', 'p.id', '=', 'cp.product_id')
            ->where('p.status', 'active')
            ->selectRaw('cp.category_id, p.id as product_id');
        $categoryCounts = DB::query()
            ->fromSub($directProducts->unionAll($linkedProducts), 'category_products')
            ->select('category_id')
            ->selectRaw('COUNT(DISTINCT product_id) as products_count')
            ->groupBy('category_id')
            ->pluck('products_count', 'category_id');
        $categories->each(fn (Category $category) => $category->setAttribute('products_count', (int) ($categoryCounts[$category->id] ?? 0)));
        $categories = $categories->filter(fn (Category $category) => (int) $category->products_count > 0)->values();

        return view('app.products.index', compact('products', 'categories', 'selectedCategories', 'pageCategory'));
    }

}
