<?php

namespace App\Http\Controllers\Admin\Explore;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductMetricEvent;
use App\Models\TrendBanner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TrendController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));

        $trendingProducts = $this->productQuery()
            ->where('is_trending', true)
            ->where('status', 'active')
            ->when($search !== '', fn (Builder $query) => $this->applySearch($query, $search))
            ->orderByDesc('downloads_count')
            ->orderByDesc('generations_count')
            ->latest('products.updated_at')
            ->paginate(24, ['*'], 'trend_page')
            ->withQueryString();

        $availableProducts = collect();
        if ($search !== '') {
            $availableProducts = $this->productQuery()
                ->where('is_trending', false)
                ->where('status', 'active')
                ->when($search !== '', fn (Builder $query) => $this->applySearch($query, $search))
                ->orderByDesc('downloads_count')
                ->orderByDesc('generations_count')
                ->latest('products.updated_at')
                ->limit(12)
                ->get();
        }

        $activeTrendingCount = Product::query()->where('status', 'active')->where('is_trending', true)->count();
        $viewsCount = ProductMetricEvent::query()
            ->where('event_type', 'view')
            ->whereHas('product', fn (Builder $query) => $query->where('status', 'active')->where('is_trending', true))
            ->count();
        $opensCount = ProductMetricEvent::query()
            ->where('event_type', 'trend_open')
            ->whereHas('product', fn (Builder $query) => $query->where('status', 'active')->where('is_trending', true))
            ->count();

        return view('admin.trends.index', [
            'trendingProducts' => $trendingProducts,
            'availableProducts' => $availableProducts,
            'banners' => TrendBanner::query()->orderBy('row_number')->orderBy('sort_order')->orderBy('id')->get(),
            'editingBanner' => $request->filled('edit_banner')
                ? TrendBanner::find($request->integer('edit_banner'))
                : null,
            'search' => $search,
            'activeTrendingCount' => $activeTrendingCount,
            'viewsCount' => $viewsCount,
            'opensCount' => $opensCount,
        ]);
    }

    public function addProduct(Product $product): RedirectResponse
    {
        abort_unless($product->status === 'active', 404);
        $product->update(['is_trending' => true]);

        return back()->with('success', 'محصول به صفحه ترند اضافه شد.');
    }

    public function toggleProduct(Product $product): RedirectResponse
    {
        $product->update(['is_trending' => ! $product->is_trending]);

        return back()->with('success', $product->is_trending
            ? 'محصول دوباره در صفحه ترند فعال شد.'
            : 'محصول از صفحه ترند حذف شد.');
    }

    public function storeBanner(Request $request): RedirectResponse
    {
        $data = $this->validatedBanner($request);
        $data['image_desktop'] = $request->file('image_desktop')?->store('trends/banners', 'public');
        $data['image_mobile'] = $request->file('image_mobile')?->store('trends/banners', 'public');

        TrendBanner::create($data);

        return redirect()->route('admin.trends.index')->with('success', 'بنر صفحه ترند اضافه شد.');
    }

    public function updateBanner(Request $request, TrendBanner $banner): RedirectResponse
    {
        $data = $this->validatedBanner($request, $banner);

        foreach (['desktop', 'mobile'] as $device) {
            $fileKey = 'image_' . $device;
            if ($request->hasFile($fileKey)) {
                $newPath = $request->file($fileKey)->store('trends/banners', 'public');
                if ($banner->{$fileKey}) {
                    Storage::disk('public')->delete($banner->{$fileKey});
                }
                $data[$fileKey] = $newPath;
            }
        }

        $banner->update($data);

        return redirect()->route('admin.trends.index')->with('success', 'تنظیمات بنر بروزرسانی شد.');
    }

    public function toggleBanner(TrendBanner $banner): RedirectResponse
    {
        $banner->update(['is_active' => ! $banner->is_active]);

        return back()->with('success', $banner->is_active ? 'بنر فعال شد.' : 'بنر غیرفعال شد.');
    }

    public function destroyBanner(TrendBanner $banner): RedirectResponse
    {
        Storage::disk('public')->delete(array_filter([$banner->image_desktop, $banner->image_mobile]));
        $banner->delete();

        return back()->with('success', 'بنر حذف شد.');
    }

    protected function productQuery(): Builder
    {
        return Product::query()
            ->with('categories:id,name_fa,name')
            ->withCount(['downloads', 'generations'])
            ->withCount(['metricEvents as views_count' => fn (Builder $query) => $query->where('event_type', 'view')])
            ->withCount(['metricEvents as trend_opens_count' => fn (Builder $query) => $query->where('event_type', 'trend_open')]);
    }

    protected function applySearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $builder) use ($search) {
            $builder->where('name_fa', 'like', "%{$search}%")
                ->orWhere('name_en', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('product_code', 'like', "%{$search}%");
        });
    }

    protected function validatedBanner(Request $request, ?TrendBanner $banner = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'display_target' => ['required', 'in:desktop,mobile,both'],
            'row_number' => ['required', 'integer', 'min:4', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
            'image_desktop' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:20480'],
            'image_mobile' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:20480'],
        ]);

        if ((int) $data['row_number'] % 4 !== 0) {
            throw ValidationException::withMessages([
                'row_number' => 'شماره ردیف بنر باید مضربی از ۴ باشد؛ مثلاً ۴، ۸ یا ۱۲.',
            ]);
        }

        if ($data['display_target'] !== 'mobile' && ! $request->hasFile('image_desktop') && ! $banner?->image_desktop) {
            throw ValidationException::withMessages(['image_desktop' => 'برای نسخه دسکتاپ تصویر بنر را انتخاب کنید.']);
        }
        if ($data['display_target'] !== 'desktop' && ! $request->hasFile('image_mobile') && ! $banner?->image_mobile) {
            throw ValidationException::withMessages(['image_mobile' => 'برای نسخه موبایل تصویر بنر را انتخاب کنید.']);
        }

        return [
            'title' => $data['title'],
            'display_target' => $data['display_target'],
            'row_number' => (int) $data['row_number'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
