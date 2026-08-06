<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * نمایش لیست دسته‌بندی‌ها به همراه تعداد محصولات هر کدام
     */
    public function index(Request $request)
    {
        // یک محصول ممکن است هم از ستون قدیمی category_id و هم از جدول چنددسته‌ای متصل شده باشد.
        // UNION باعث می‌شود هر اتصال محصول/دسته فقط یک‌بار در آمار شمرده شود.
        $productStats = DB::query()
            ->fromSub($this->categoryProductPairs(), 'category_products')
            ->join('products', function ($join) {
                $join->on('products.id', '=', 'category_products.product_id')
                    ->whereNull('products.deleted_at');
            })
            ->selectRaw('category_products.category_id, COUNT(DISTINCT category_products.product_id) as products_count, MAX(products.created_at) as last_product_at')
            ->groupBy('category_products.category_id')
            ->get()
            ->keyBy('category_id');

        $productCounts = $productStats->mapWithKeys(fn ($stat, $categoryId) => [
            $categoryId => (int) $stat->products_count,
        ]);

        $usageCounts = DB::query()
            ->fromSub($this->categoryProductPairs(), 'category_products')
            ->join('generations', 'generations.product_id', '=', 'category_products.product_id')
            ->selectRaw('category_products.category_id, COUNT(generations.id) as aggregate')
            ->groupBy('category_products.category_id')
            ->pluck('aggregate', 'category_products.category_id');

        $query = Category::query();
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('name_fa', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%");
            });
        }

        if ($request->input('visibility') === 'enabled') $query->where('is_active', true);
        if ($request->input('visibility') === 'disabled') $query->where('is_active', false);
        if ($request->input('type') === 'root') $query->whereNull('parent_id');
        if ($request->input('type') === 'child') $query->whereNotNull('parent_id');
        if ($request->input('type') === 'featured') $query->where('is_featured', true);

        $allFiltered = $query->get()->each(function (Category $category) use ($productStats, $usageCounts) {
            $stat = $productStats->get($category->id);
            $category->setAttribute('products_count', (int) ($stat?->products_count ?? 0));
            $category->setAttribute('last_product_at', $stat?->last_product_at ? Carbon::parse($stat->last_product_at) : null);
            $category->setAttribute('usage_count', (int) ($usageCounts[$category->id] ?? 0));
        });

        if ($request->input('content') === 'active') $allFiltered = $allFiltered->where('products_count', '>', 0);
        if ($request->input('content') === 'empty') $allFiltered = $allFiltered->where('products_count', 0);

        $allFiltered = (match ($request->input('sort', 'products_desc')) {
            'products', 'products_desc' => $allFiltered->sortByDesc('products_count'),
            'products_asc' => $allFiltered->sortBy('products_count'),
            'name' => $allFiltered->sortBy(fn ($category) => $category->name_fa ?: $category->name),
            'latest' => $allFiltered->sortByDesc('created_at'),
            'oldest' => $allFiltered->sortBy('created_at'),
            'last_product' => $allFiltered->sortByDesc(fn ($category) => $category->last_product_at?->getTimestamp() ?? 0),
            'first_product' => $allFiltered->sortBy(fn ($category) => $category->last_product_at?->getTimestamp() ?? PHP_INT_MAX),
            'usage' => $allFiltered->sortByDesc('usage_count'),
            default => $allFiltered->sortByDesc('products_count'),
        })->values();

        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $categories = new LengthAwarePaginator(
            $allFiltered->forPage($page, $perPage)->values(),
            $allFiltered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $allCategories = Category::all();
        $totalCategories = $allCategories->count();
        $activeCategories = $allCategories->whereIn('id', $productCounts->filter(fn ($count) => $count > 0)->keys())->count();
        $emptyCategories = $totalCategories - $activeCategories;
        $topCategoryId = $usageCounts->sortDesc()->keys()->first();
        $topCategory = $topCategoryId ? $allCategories->firstWhere('id', (int) $topCategoryId) : null;
        $totalUsage = (int) $usageCounts->sum();

        return view('admin.categories.index', compact(
            'categories', 'totalCategories', 'activeCategories', 'emptyCategories',
            'topCategory', 'totalUsage', 'usageCounts'
        ));
    }

    private function categoryProductPairs()
    {
        $legacy = DB::table('products')
            ->whereNotNull('category_id')
            ->whereNull('deleted_at')
            ->select('category_id', DB::raw('id as product_id'));

        return $legacy->union(
            DB::table('category_product')
                ->join('products', function ($join) {
                    $join->on('products.id', '=', 'category_product.product_id')
                        ->whereNull('products.deleted_at');
                })
                ->select('category_product.category_id', 'category_product.product_id')
        );
    }

    /**
     * نمایش فرم ساخت دسته‌بندی جدید
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * ذخیره دسته‌بندی جدید در دیتابیس
     */
    public function store(Request $request)
    {
        // ولیدیشن دقیق فیلدها و حجم فایل تصویر
        $request->validate([
            'name'  => 'required|string|max:255|unique:categories,name',
            'slug'  => 'nullable|string|max:255|unique:categories,slug',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'parent_id' => 'nullable|exists:categories,id', // حداکثر ۲ مگابایت
            'custom_url' => ['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/|\/)[^\s]+$/i'],
        ], [
            'name.required' => 'وارد کردن نام دسته‌بندی الزامی است.',
            'name.unique'   => 'این نام دسته‌بندی قبلاً ثبت شده است.',
            'slug.unique'   => 'این اسلاگ تکراری است. لطفاً اسلاگ دیگری وارد کنید.',
            'image.image'   => 'فایل انتخابی باید از نوع تصویر باشد.',
            'image.mimes'   => 'فرمت‌های مجاز برای تصویر: jpeg, png, jpg, webp',
            'image.max'     => 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد.',
        ]);

        $data = $request->only(['name']);
        
        // هندل کردن اسلاگ فارسی و انگلیسی (جلوگیری از حذف حروف فارسی توسط لاراول)
        $slugSource = $request->slug ? $request->slug : $request->name;
        $data['slug'] = preg_replace('/\s+/u', '-', trim($slugSource));

        // ساختار درختی: سرشاخه/زیرشاخه + فیلدهای الزامی و مسیر سئو
        $data['parent_id'] = $request->input('parent_id') ?: null;
        $data['custom_url'] = $request->filled('custom_url') ? trim($request->input('custom_url')) : null;
        $data['name_fa']   = $request->name;
        $data['name_en']   = $request->input('name_en') ?: $request->name;
        $parentCat = $data['parent_id'] ? Category::find($data['parent_id']) : null;
        $data['path'] = ($parentCat && $parentCat->path) ? ($parentCat->path . '/' . $data['slug']) : $data['slug'];

        // آپلود فیزیکی تصویر روی استوریج محلی پروژه (بدون نیاز به CDN)
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        // ایجاد رکورد در دیتابیس
        Category::create($data);

        // ریدایرکت به صفجه ایندکس همراه با فلش سشن برای نمایش توست موفقیت
        return redirect()->route('admin.categories.index')->with('success', 'دسته‌بندی جدید با موفقیت ثبت و اضافه شد.');
    }

    /**
     * نمایش فرم ویرایش دسته‌بندی
     */
    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * بروزرسانی اطلاعات دسته‌بندی
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name'  => 'required|string|max:255|unique:categories,name,' . $category->id,
            'slug'  => 'nullable|string|max:255|unique:categories,slug,' . $category->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'parent_id' => 'nullable|exists:categories,id',
            'custom_url' => ['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/|\/)[^\s]+$/i'],
        ], [
            'name.required' => 'وارد کردن نام دسته‌بندی الزامی است.',
            'name.unique'   => 'این نام دسته‌بندی قبلاً ثبت شده است.',
            'slug.unique'   => 'این اسلاگ تکراری است.',
            'image.image'   => 'فایل انتخابی باید تصویر باشد.',
        ]);

        $data = $request->only(['name']);
        
        $slugSource = $request->slug ? $request->slug : $request->name;
        $data['slug'] = preg_replace('/\s+/u', '-', trim($slugSource));

        // ساختار درختی: سرشاخه/زیرشاخه + فیلدهای الزامی و مسیر سئو
        $data['parent_id'] = $request->input('parent_id') ?: null;
        $data['custom_url'] = $request->filled('custom_url') ? trim($request->input('custom_url')) : null;
        $data['name_fa']   = $request->name;
        $data['name_en']   = $request->input('name_en') ?: $request->name;
        $parentCat = $data['parent_id'] ? Category::find($data['parent_id']) : null;
        $data['path'] = ($parentCat && $parentCat->path) ? ($parentCat->path . '/' . $data['slug']) : $data['slug'];

        // مدیریت جایگزینی تصویر جدید و حذف تصویر قبلی از هاست
        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'تغییرات دسته‌بندی با موفقیت ذخیره شد.');
    }

    /**
     * حذف دسته‌بندی
     */
    public function destroy(Category $category)
    {
        // ۱. حذف فایل تصویر از پوشه storage برای جلوگیری از پر شدن هاست
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        // ۲. حذف خود رکورد دسته‌بندی
        // نکته: به دلیل وجود onDelete('set null') در مایگریشن، محصولات وابسته آسیب نمیبینند و فیلد category_id آنها null میشود.
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'دسته‌بندی با موفقیت حذف شد و محصولات وابسته به حالت بدون دسته‌بندی درآمدند.');
    }
}
