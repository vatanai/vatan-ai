<?php

namespace App\Http\Controllers\Admin\HomeBuilder;

use App\Http\Controllers\Controller;
use App\Http\Requests\HomeBuilder\StoreHomeSectionRequest;
use App\Http\Requests\HomeBuilder\UpdateHomeSectionRequest;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * مدیریت صفحه Home (Home Builder) — یک بخش کاملاً مجزا در ادمین.
 * هیچ فایل/Controller بخش‌های دیگر (محصولات، اکسپلور، سفارشات، ...) از اینجا لمس نمی‌شود.
 */
class HomeBuilderController extends Controller
{
    protected string $pageKey;

    public function __construct()
    {
        $this->pageKey = config('home_builder.default_page_key', HomeSection::DEFAULT_PAGE_KEY);
    }

    /**
     * جستجوی محصولات فعال برای فیلد «انتخاب دستی محصولات» در Drawer تنظیمات.
     * خروجی سبک (id + نام) برای ساخت چیپ‌های انتخاب‌شده در فرم ادمین.
     */
    public function searchProducts(\Illuminate\Http\Request $request)
    {
        $term = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->where('status', 'active')
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($qq) use ($term) {
                    $qq->where('name_fa', 'like', "%{$term}%")
                       ->orWhere('name', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->limit(10)
            ->get(['id', 'name_fa']);

        return response()->json([
            'products' => $products->map(fn (Product $p) => ['id' => $p->id, 'name' => $p->name_fa])->values(),
        ]);
    }

    public function index()
    {
        $sections = HomeSection::forPage($this->pageKey)->ordered()->get();

        $categories = Category::query()->active()->orderBy('name_fa')->get(['id', 'name_fa']);

        return view('admin.home-builder.index', [
            'sections' => $sections,
            'categories' => $categories,
            'typeRegistry' => config('home_builder.types'),
            'statuses' => config('home_builder.statuses'),
        ]);
    }

    public function store(StoreHomeSectionRequest $request)
    {
        $data = $request->validated();

        $maxPosition = (int) HomeSection::forPage($this->pageKey)->max('position');

        $section = HomeSection::create([
            'page_key' => $this->pageKey,
            'type' => $data['type'],
            'layout' => $data['layout'] ?? 'default',
            'title_fa' => $data['title_fa'] ?? null,
            'subtitle_fa' => $data['subtitle_fa'] ?? null,
            'settings' => $data['settings'] ?? [],
            'responsive' => $data['responsive'] ?? null,
            'status' => HomeSection::STATUS_DRAFT,
            'position' => $maxPosition + 1,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'section' => $section]);
        }

        return back()->with('success', 'Section جدید ایجاد شد.');
    }

    public function update(UpdateHomeSectionRequest $request, HomeSection $homeSection)
    {
        $this->guardSamePage($homeSection);

        $data = $request->validated();

        $homeSection->fill([
            'layout' => $data['layout'] ?? $homeSection->layout,
            'title_fa' => $data['title_fa'] ?? null,
            'subtitle_fa' => $data['subtitle_fa'] ?? null,
            'settings' => $data['settings'] ?? [],
            'responsive' => $data['responsive'] ?? $homeSection->responsive,
        ]);

        if (! empty($data['status']) && $data['status'] !== $homeSection->status) {
            $this->applyStatus($homeSection, $data['status']);
        }

        $homeSection->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'section' => $homeSection->fresh()]);
        }

        return back()->with('success', 'Section بروزرسانی شد.');
    }

    public function destroy(Request $request, HomeSection $homeSection)
    {
        $this->guardSamePage($homeSection);

        $homeSection->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Section حذف شد.');
    }

    public function duplicate(Request $request, HomeSection $homeSection)
    {
        $this->guardSamePage($homeSection);

        $maxPosition = (int) HomeSection::forPage($this->pageKey)->max('position');

        $copy = $homeSection->replicate(['status', 'published_at']);
        $copy->title_fa = trim(($homeSection->title_fa ?: $homeSection->type) . ' (کپی)');
        $copy->status = HomeSection::STATUS_DRAFT;
        $copy->published_at = null;
        $copy->position = $maxPosition + 1;
        $copy->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'section' => $copy]);
        }

        return back()->with('success', 'Section تکثیر شد.');
    }

    public function updateStatus(Request $request, HomeSection $homeSection)
    {
        $this->guardSamePage($homeSection);

        $data = $request->validate([
            'status' => 'required|in:' . implode(',', HomeSection::STATUSES),
        ]);

        $this->applyStatus($homeSection, $data['status']);
        $homeSection->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'status' => $homeSection->status]);
        }

        return back()->with('success', 'وضعیت Section تغییر کرد.');
    }

    /** جابه‌جایی عمودی (Drag & Drop) — کل ترتیب جدید یک‌جا از فرانت ارسال می‌شود. */
    public function reorder(Request $request)
    {
        $data = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'integer|exists:home_sections,id',
        ]);

        foreach ($data['order'] as $index => $id) {
            HomeSection::forPage($this->pageKey)->where('id', $id)->update(['position' => $index + 1]);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'ترتیب Sectionها بروزرسانی شد.');
    }

    protected function applyStatus(HomeSection $homeSection, string $status): void
    {
        $homeSection->status = $status;
        if ($status === HomeSection::STATUS_PUBLISHED && ! $homeSection->published_at) {
            $homeSection->published_at = now();
        }
    }

    /** اطمینان از اینکه Section درخواستی متعلق به همین صفحه (app_home) است، نه صفحه‌ای دیگر در آینده. */
    protected function guardSamePage(HomeSection $homeSection): void
    {
        abort_unless($homeSection->page_key === $this->pageKey, 404);
    }
}
