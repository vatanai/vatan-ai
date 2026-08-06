<?php

namespace App\Http\Controllers\Admin\Explore;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\TrendOccasion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrendOccasionController extends Controller
{
    public function index(): View
    {
        return view('admin.trends.index', [
            'occasions' => TrendOccasion::with('category')->latest('created_at')->get(),
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('id')->get(['id', 'name_fa', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        TrendOccasion::create($this->validated($request));

        return back()->with('success', 'مناسبت ترند با موفقیت اضافه شد.');
    }

    public function update(Request $request, TrendOccasion $occasion): RedirectResponse
    {
        $occasion->update($this->validated($request));

        return back()->with('success', 'مناسبت ترند بروزرسانی شد.');
    }

    public function toggle(TrendOccasion $occasion): RedirectResponse
    {
        $occasion->update(['is_active' => ! $occasion->is_active]);

        return back()->with('success', 'وضعیت مناسبت تغییر کرد.');
    }

    public function destroy(TrendOccasion $occasion): RedirectResponse
    {
        $occasion->delete();

        return back()->with('success', 'مناسبت حذف شد.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'title_fa' => ['required', 'string', 'max:120'],
            'query' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['query'] = trim((string) ($data['query'] ?? '')) ?: $data['title_fa'];
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
