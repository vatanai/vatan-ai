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

    public function category(Request $request, string $path)
    {
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

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('name_fa', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('description_fa', 'like', "%{$search}%")
                    ->orWhere('tags', 'like', "%{$search}%");
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

        return view('app.products.index', compact('products', 'categories', 'selectedCategories', 'pageCategory'));
    }
}
