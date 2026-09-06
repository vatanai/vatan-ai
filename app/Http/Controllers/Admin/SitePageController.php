<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSitePageRequest;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\SitePage;
use App\Models\SitePageRevision;
use App\Services\SitePageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SitePageController extends Controller
{
    public function __construct(private readonly SitePageService $pageService)
    {
    }

    public function index(): View
    {
        $storedPages = SitePage::query()->with('updatedBy')->get()->keyBy('key');
        $activeProducts = Product::query()->where('status', 'active')->count();
        $trendingProducts = Product::query()->where('status', 'active')->where('is_trending', true)->count();
        $homeSections = HomeSection::query()->count();

        $metrics = [
            'home' => "{$homeSections} سکشن",
            'explore' => "{$activeProducts} محصول فعال",
            'trends' => "{$trendingProducts} محصول ترند",
            'create' => "{$activeProducts} محصول قابل ساخت",
            'profile' => 'تنظیمات حساب کاربر',
            'articles' => 'مدیریت محتوای آموزشی',
        ];

        $pages = collect(config('site_pages.pages'))->map(function (array $definition, string $key) use ($storedPages, $metrics) {
            $page = $storedPages->get($key);

            return [
                ...$definition,
                'key' => $key,
                'page' => $page,
                'metric' => $metrics[$key] ?? '—',
                'preview_url' => ! empty($definition['route_names'][0]) && \Route::has($definition['route_names'][0])
                    ? route($definition['route_names'][0])
                    : null,
                'manage_url' => $page ? route('admin.pages.edit', $page) : null,
                'status' => $page?->status ?? 'draft',
                'status_label' => $this->statusLabel($page?->status ?? 'draft'),
            ];
        })->values();

        return view('admin.pages.index', [
            'pages' => $pages,
            'connectedCount' => $pages->where('page', '!=', null)->count(),
            'publishedCount' => $pages->where('status', 'published')->count(),
            'draftCount' => $pages->whereIn('status', ['draft', 'archived'])->count(),
        ]);
    }

    public function edit(SitePage $sitePage): View
    {
        $definition = config("site_pages.pages.{$sitePage->key}");
        abort_unless($definition, 404);

        return view('admin.pages.edit', [
            'page' => $sitePage,
            'definition' => $definition,
            'revisions' => $sitePage->revisions()->with('admin')->limit(12)->get(),
            'previewUrl' => \Route::has($definition['route_names'][0] ?? '') ? route($definition['route_names'][0]) : null,
            'advancedUrl' => ! empty($definition['advanced_route']) && \Route::has($definition['advanced_route'])
                ? route($definition['advanced_route'])
                : null,
        ]);
    }

    public function update(UpdateSitePageRequest $request, SitePage $sitePage): RedirectResponse
    {
        $data = $request->validated();
        $oldOgImage = $sitePage->og_image;

        DB::transaction(function () use ($request, $sitePage, $data) {
            $pageData = collect($data)->only([
                'name_fa', 'name_en', 'status', 'title', 'subtitle', 'meta_title', 'meta_description',
                'canonical_url', 'is_indexable', 'requires_auth', 'maintenance_mode',
                'maintenance_message', 'scheduled_at',
            ])->all();

            $pageData['meta_keywords'] = collect(preg_split('/[,،]/u', (string) ($data['meta_keywords'] ?? '')))
                ->map(fn ($keyword) => trim($keyword))->filter()->unique()->values()->all();
            $pageData['display_settings'] = [
                'layout_width' => $sitePage->display('layout_width', 'default'),
                'theme' => $data['theme'],
                'show_footer' => $data['show_footer'],
            ];
            $pageData['content_settings'] = [
                'show_page_title' => $data['show_page_title'],
                'show_search' => $data['show_search'],
                'items_per_page' => (int) $data['items_per_page'],
                'cache_ttl' => $sitePage->content('cache_ttl', 300),
            ];

            if ($request->hasFile('og_image')) {
                $pageData['og_image'] = $request->file('og_image')->store('site-pages/og', 'public');
            }

            if ($pageData['status'] === 'published' && ! $sitePage->published_at) {
                $pageData['published_at'] = now();
            }

            $pageData['version'] = $sitePage->version + 1;
            $pageData['updated_by'] = $request->user('admin')?->id;
            $sitePage->update($pageData);
            $this->recordRevision($sitePage->fresh(), 'updated', $data['change_note'] ?? null, $request);
        });

        if ($request->hasFile('og_image') && $oldOgImage && $oldOgImage !== $sitePage->fresh()->og_image) {
            Storage::disk('public')->delete($oldOgImage);
        }

        $this->pageService->forget($sitePage);

        return back()->with('success', 'تنظیمات صفحه با موفقیت ذخیره شد.');
    }

    public function publish(Request $request, SitePage $sitePage): RedirectResponse
    {
        $data = $request->validate(['status' => 'required|in:draft,published,archived']);

        DB::transaction(function () use ($request, $sitePage, $data) {
            $sitePage->update([
                'status' => $data['status'],
                'published_at' => $data['status'] === 'published' ? ($sitePage->published_at ?: now()) : $sitePage->published_at,
                'version' => $sitePage->version + 1,
                'updated_by' => $request->user('admin')?->id,
            ]);
            $this->recordRevision($sitePage->fresh(), $data['status'], 'تغییر سریع وضعیت انتشار', $request);
        });

        $this->pageService->forget($sitePage);

        return back()->with('success', 'وضعیت انتشار صفحه تغییر کرد.');
    }

    public function restore(Request $request, SitePage $sitePage, SitePageRevision $revision): RedirectResponse
    {
        abort_unless($revision->site_page_id === $sitePage->id, 404);

        DB::transaction(function () use ($request, $sitePage, $revision) {
            $snapshot = collect($revision->snapshot)->except(['version', 'updated_by'])->all();
            $sitePage->fill($snapshot);
            $sitePage->version++;
            $sitePage->updated_by = $request->user('admin')?->id;
            $sitePage->save();
            $this->recordRevision($sitePage->fresh(), 'restored', "بازیابی نسخه {$revision->version}", $request);
        });

        $this->pageService->forget($sitePage);

        return back()->with('success', "نسخه {$revision->version} با موفقیت بازیابی شد.");
    }

    private function recordRevision(SitePage $page, string $action, ?string $note, Request $request): void
    {
        $page->revisions()->create([
            'version' => $page->version,
            'snapshot' => $page->snapshot(),
            'action' => $action,
            'change_note' => $note,
            'admin_id' => $request->user('admin')?->id,
        ]);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'published' => 'منتشرشده',
            'scheduled' => 'زمان‌بندی‌شده',
            'archived' => 'بایگانی‌شده',
            default => 'پیش‌نویس',
        };
    }
}
