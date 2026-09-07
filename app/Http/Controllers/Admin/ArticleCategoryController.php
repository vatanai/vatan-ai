<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ArticleCategoryController extends Controller
{
    public function index(): View
    {
        $categories = ArticleCategory::with('parent')->withCount('articles')->orderBy('sort_order')->get();

        return view('admin.articles.categories', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        ArticleCategory::create($this->validated($request));

        return back()->with('success', 'دسته‌بندی مقاله ثبت شد.');
    }

    public function update(Request $request, ArticleCategory $articleCategory): RedirectResponse
    {
        $articleCategory->update($this->validated($request, $articleCategory));

        return back()->with('success', 'دسته‌بندی بروزرسانی شد.');
    }

    public function destroy(ArticleCategory $articleCategory): RedirectResponse
    {
        if ($articleCategory->articles()->exists() || $articleCategory->children()->exists()) {
            return back()->with('error', 'برای حذف این دسته ابتدا مقاله‌ها و زیرشاخه‌های آن را منتقل کنید.');
        }
        $articleCategory->delete();

        return back()->with('success', 'دسته‌بندی حذف شد.');
    }

    private function validated(Request $request, ?ArticleCategory $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:170', Rule::unique('article_categories', 'slug')->ignore($category?->id)],
            'parent_id' => ['nullable', 'exists:article_categories,id', Rule::notIn([$category?->id])],
            'description' => ['nullable', 'string', 'max:2000'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']) ?: 'category-' . Str::lower(Str::random(8));
        $data['meta_title'] = $data['meta_title'] ?: $data['name'] . ' | مقالات وطن';
        $data['meta_description'] = $data['meta_description'] ?: $data['description'];
        $data['is_active'] = $request->boolean('is_active');
        $data['is_indexable'] = $request->boolean('is_indexable');
        $data['sort_order'] ??= 0;

        if ($category && ! empty($data['parent_id'])) {
            $parent = ArticleCategory::query()->find($data['parent_id']);
            while ($parent) {
                if ($parent->is($category)) {
                    throw ValidationException::withMessages(['parent_id' => 'یک زیرشاخه نمی‌تواند والد شاخه اصلی خودش باشد.']);
                }
                $parent = $parent->parent;
            }
        }

        return $data;
    }
}
