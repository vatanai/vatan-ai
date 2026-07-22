<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $query = Discount::query()->withCount('orders');
        $query->when($request->filled('q'), fn ($q) => $q->where(fn ($s) => $s
            ->where('name', 'like', '%' . trim($request->q) . '%')
            ->orWhere('code', 'like', '%' . trim($request->q) . '%')));
        $query->when($request->status === 'active', fn ($q) => $q->available());
        $query->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false));
        $query->when($request->status === 'expired', fn ($q) => $q->whereNotNull('ends_at')->where('ends_at', '<', now()));

        $discounts = $query->latest()->paginate(20)->withQueryString();
        $products = Product::select('id', 'name_fa')->orderBy('name_fa')->get();
        $categories = Category::select('id', 'name_fa', 'name')->orderByRaw('COALESCE(name_fa, name)')->get();
        $stats = [
            'total' => Discount::count(), 'active' => Discount::available()->count(),
            'uses' => (int) Discount::sum('used_count'),
            'credits' => (int) \App\Models\Order::sum('discount_credits'),
        ];
        return view('admin.discounts.index', compact('discounts', 'products', 'categories', 'stats'));
    }

    public function store(Request $request)
    {
        Discount::create($this->validated($request));
        return back()->with('success', 'تخفیف با موفقیت ساخته شد.');
    }

    public function update(Request $request, Discount $discount)
    {
        $discount->update($this->validated($request, $discount));
        return back()->with('success', 'تخفیف بروزرسانی شد.');
    }

    public function toggle(Discount $discount)
    {
        $discount->update(['is_active' => !$discount->is_active]);
        return back()->with('success', $discount->is_active ? 'تخفیف فعال شد.' : 'تخفیف غیرفعال شد.');
    }

    public function destroy(Discount $discount)
    {
        if ($discount->orders()->exists()) {
            $discount->update(['is_active' => false]);
            return back()->with('success', 'این تخفیف سابقه استفاده دارد و برای حفظ گزارش‌ها غیرفعال شد.');
        }
        $discount->delete();
        return back()->with('success', 'تخفیف حذف شد.');
    }

    private function validated(Request $request, ?Discount $discount = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', Rule::unique('discounts', 'code')->ignore($discount)],
            'type' => ['required', Rule::in(['percent', 'fixed', 'free'])],
            'value' => ['required', 'integer', 'min:0', 'max:1000000'],
            'max_discount_credits' => ['nullable', 'integer', 'min:1'],
            'min_order_credits' => ['nullable', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['required', 'integer', 'min:1'],
            'scope' => ['required', Rule::in(['all', 'products', 'categories'])],
            'product_ids' => ['nullable', 'array'], 'product_ids.*' => ['integer', 'exists:products,id'],
            'category_ids' => ['nullable', 'array'], 'category_ids.*' => ['integer', 'exists:categories,id'],
            'first_order_only' => ['nullable', 'boolean'], 'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['code'] = strtoupper(trim($data['code']));
        $data['min_order_credits'] ??= 0;
        $data['first_order_only'] = $request->boolean('first_order_only');
        $data['is_active'] = $request->boolean('is_active');
        if ($data['type'] === 'percent' && $data['value'] > 100) abort(422, 'درصد تخفیف نمی‌تواند بیشتر از ۱۰۰ باشد.');
        if ($data['scope'] !== 'products') $data['product_ids'] = null;
        if ($data['scope'] !== 'categories') $data['category_ids'] = null;
        return $data;
    }
}
