<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveArticleRequest;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleCategory;
use App\Models\ArticleGallery;
use App\Models\ArticleRedirect;
use App\Models\ArticleRevision;
use App\Models\ArticleTag;
use App\Models\Admin;
use App\Models\Category;
use App\Models\GeneratedImage;
use App\Models\GrowthContent;
use App\Models\Product;
use App\Services\ArticleContentService;
use App\Services\ArticleSeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleSeoService $seo,
        private readonly ArticleContentService $content,
    ) {
    }

    public function index(Request $request): View
    {
        $query = Article::query()->with(['category', 'author'])
            ->withCount(['approvedComments as comments_count'])
            ->withCount(['comments as pending_comments_count' => fn ($comments) => $comments->where('status', 'pending')]);

        if ($request->query('status') === 'trash') {
            $query->onlyTrashed();
        } elseif ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($categoryId = $request->integer('category_id')) $query->where('article_category_id', $categoryId);
        if ($authorId = $request->integer('author_id')) $query->where('article_author_id', $authorId);
        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn ($articles) => $articles->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('excerpt', 'like', "%{$search}%"));
        }

        $articles = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();
        $stats = [
            'all' => Article::count(),
            'published' => Article::where('status', 'published')->count(),
            'draft' => Article::whereIn('status', ['draft', 'changes_requested'])->count(),
            'scheduled' => Article::where('status', 'scheduled')->count(),
            'views' => (int) Article::sum('view_count'),
            'pending_comments' => (int) DB::table('article_comments')->where('status', 'pending')->count(),
        ];
        $categories = ArticleCategory::orderBy('sort_order')->get();
        $authors = ArticleAuthor::where('is_active', true)->orderBy('name')->get();

        return view('admin.articles.index', compact('articles', 'stats', 'categories', 'authors'));
    }

    public function create(): View
    {
        return view('admin.articles.form', $this->formData(new Article()));
    }

    public function store(SaveArticleRequest $request): RedirectResponse
    {
        $this->assertCanPublish($request);

        $article = DB::transaction(function () use ($request) {
            $data = $this->prepareData($request);
            $data['created_by'] = $request->user('admin')->id;
            $data['updated_by'] = $request->user('admin')->id;
            $tags = $data['tags_list'];
            unset($data['tags_list'], $data['galleries'], $data['product_ids'], $data['remove_gallery_ids'], $data['change_note']);

            $article = Article::create($data);
            $this->syncTags($article, $tags);
            $this->syncProducts($article, $request->input('product_ids', []));
            $this->syncGalleries($article, $request);
            $this->recordRevision($article, 'created', $request->input('change_note'), $request);
            $this->syncGrowthContent($article);

            return $article;
        });

        return redirect()->route('admin.articles.edit', $article)->with('success', 'مقاله با موفقیت ثبت شد.');
    }

    public function edit(Article $article): View
    {
        $article->load(['tags', 'products', 'galleries.items', 'revisions']);

        return view('admin.articles.form', $this->formData($article));
    }

    public function update(SaveArticleRequest $request, Article $article): RedirectResponse
    {
        $this->assertCanPublish($request, $article);

        DB::transaction(function () use ($request, $article) {
            $oldSlug = $article->slug;
            $data = $this->prepareData($request, $article);
            $data['updated_by'] = $request->user('admin')->id;
            $tags = $data['tags_list'];
            unset($data['tags_list'], $data['galleries'], $data['product_ids'], $data['remove_gallery_ids'], $data['change_note']);
            $article->update($data);

            if ($oldSlug !== $article->slug) {
                ArticleRedirect::updateOrCreate(['old_slug' => $oldSlug], ['article_id' => $article->id, 'is_active' => true]);
            }

            $this->syncTags($article, $tags);
            $this->syncProducts($article, $request->input('product_ids', []));
            $this->syncGalleries($article, $request);
            $this->recordRevision($article->fresh(), 'updated', $request->input('change_note'), $request);
            $this->syncGrowthContent($article->fresh());
        });

        return back()->with('success', 'تغییرات مقاله ذخیره شد.');
    }

    public function archive(Request $request, Article $article): RedirectResponse
    {
        $article->update(['status' => 'archived', 'archived_at' => now(), 'updated_by' => $request->user('admin')->id]);
        $this->recordRevision($article->fresh(), 'archived', 'انتقال به آرشیو', $request);
        $this->syncGrowthContent($article->fresh());

        return back()->with('success', 'مقاله آرشیو شد.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return redirect()->route('admin.articles.index')->with('success', 'مقاله به زباله‌دان منتقل شد.');
    }

    public function restore(int $article): RedirectResponse
    {
        $model = Article::onlyTrashed()->findOrFail($article);
        $model->restore();

        return back()->with('success', 'مقاله بازیابی شد.');
    }

    public function forceDelete(Request $request, int $article): RedirectResponse
    {
        abort_unless($request->user('admin')?->isLeader(), 403);
        Article::onlyTrashed()->findOrFail($article)->forceDelete();

        return back()->with('success', 'مقاله برای همیشه حذف شد.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'article_ids' => ['required', 'array', 'min:1'],
            'article_ids.*' => ['integer', 'distinct', 'exists:articles,id'],
            'action' => ['required', 'in:publish,archive,delete'],
        ]);
        if ($data['action'] === 'publish') {
            abort_unless($request->user('admin')?->isLeader(), 403);
        }
        DB::transaction(function () use ($data, $request) {
            Article::query()->whereIn('id', $data['article_ids'])->get()->each(function (Article $article) use ($data, $request) {
                if ($data['action'] === 'publish') {
                    $article->update([
                        'status' => 'published', 'published_at' => $article->published_at ?: now(),
                        'scheduled_at' => null, 'archived_at' => null, 'updated_by' => $request->user('admin')->id,
                    ]);
                    $this->recordRevision($article->fresh(), 'published', 'انتشار گروهی', $request);
                    $this->syncGrowthContent($article->fresh());
                } elseif ($data['action'] === 'archive') {
                    $article->update(['status' => 'archived', 'archived_at' => now(), 'updated_by' => $request->user('admin')->id]);
                    $this->recordRevision($article->fresh(), 'archived', 'آرشیو گروهی', $request);
                    $this->syncGrowthContent($article->fresh());
                } else {
                    $this->recordRevision($article, 'deleted', 'حذف گروهی', $request);
                    $article->delete();
                }
            });
        });

        return back()->with('success', 'عملیات گروهی انجام شد.');
    }

    public function analytics(Article $article): View
    {
        $article->load(['category', 'author'])->loadCount(['approvedComments as comments_count']);
        $eventCounts = $article->events()->select('event_type', DB::raw('count(*) as total'))->groupBy('event_type')->pluck('total', 'event_type');
        $recentEvents = $article->events()->latest('created_at')->limit(1000)->get(['event_type', 'created_at']);
        $daily = $recentEvents->groupBy(fn ($event) => $event->created_at->timezone('Asia/Tehran')->toDateString())
            ->map(fn ($events) => ['views' => $events->where('event_type', 'view')->count(), 'events' => $events->count()])
            ->sortKeys();

        return view('admin.articles.analytics', compact('article', 'eventCounts', 'daily'));
    }

    public function overviewAnalytics(): View
    {
        $totals = [
            'articles' => Article::count(),
            'published' => Article::where('status', 'published')->count(),
            'views' => (int) Article::sum('view_count'),
            'unique_views' => (int) Article::sum('unique_view_count'),
            'shares' => (int) Article::sum('share_count'),
            'cta_clicks' => (int) Article::sum('cta_click_count'),
        ];
        $topArticles = Article::query()->with('category')->withCount(['approvedComments as comments_count'])
            ->orderByDesc('view_count')->limit(15)->get();
        $events = DB::table('article_events')->where('created_at', '>=', now()->subDays(30))
            ->latest('created_at')->limit(20000)->get(['event_type', 'metadata', 'created_at']);
        $eventCounts = $events->countBy('event_type');
        $daily = $events->groupBy(fn ($event) => substr((string) $event->created_at, 0, 10))
            ->map(fn ($items) => ['views' => $items->where('event_type', 'view')->count(), 'events' => $items->count()])
            ->sortKeys();
        $categoryStats = ArticleCategory::query()->withCount('articles')
            ->withSum('articles', 'view_count')->orderByDesc('articles_sum_view_count')->get();

        return view('admin.articles.analytics-overview', compact('totals', 'topArticles', 'eventCounts', 'daily', 'categoryStats'));
    }

    public function restoreRevision(Request $request, Article $article, ArticleRevision $revision): RedirectResponse
    {
        abort_unless($revision->article_id === $article->id, 404);
        $snapshot = collect($revision->snapshot)->only($article->getFillable())->all();
        $article->update($snapshot + ['updated_by' => $request->user('admin')->id]);
        $this->recordRevision($article->fresh(), 'restored', 'بازیابی نسخه ' . $revision->version, $request);

        return back()->with('success', 'نسخه انتخاب‌شده بازیابی شد.');
    }

    private function prepareData(SaveArticleRequest $request, ?Article $article = null): array
    {
        $data = $request->validated();
        $data['content_blocks'] = $this->content->normalize($data['content_blocks']);
        $data = $this->seo->prepare($data, $article);

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = 'storage/' . $request->file('featured_image')->store('articles/featured', 'public');
        } elseif (! empty($data['featured_image_path'])) {
            $data['featured_image'] = ltrim($data['featured_image_path'], '/');
        } else {
            unset($data['featured_image']);
        }
        unset($data['featured_image_path']);

        if ($request->hasFile('og_image')) {
            $data['og_image'] = 'storage/' . $request->file('og_image')->store('articles/og', 'public');
        } elseif ($data['seo_auto_fill'] && empty($data['og_image'])) {
            $data['og_image'] = $data['featured_image'] ?? $article?->featured_image;
        } else {
            unset($data['og_image']);
        }

        if ($data['status'] === 'published') {
            $data['published_at'] = $data['published_at'] ?? $article?->published_at ?? now();
            $data['scheduled_at'] = null;
            $data['archived_at'] = null;
        } elseif ($data['status'] === 'scheduled') {
            $data['published_at'] = $data['scheduled_at'];
            $data['archived_at'] = null;
        } elseif ($data['status'] === 'archived') {
            $data['archived_at'] = $article?->archived_at ?? now();
        }

        return $data;
    }

    private function syncTags(Article $article, array $tagNames): void
    {
        $ids = collect($tagNames)->map(function (string $name) {
            $slug = Str::slug($name) ?: 'tag-' . substr(sha1($name), 0, 12);
            return ArticleTag::firstOrCreate(['slug' => $slug], ['name' => $name, 'is_active' => true])->id;
        })->all();
        $article->tags()->sync($ids);
    }

    private function syncProducts(Article $article, array $productIds): void
    {
        $sync = collect($productIds)->unique()->values()->mapWithKeys(fn ($id, $index) => [(int) $id => [
            'placement' => 'related', 'cta_label' => 'ساخت با این محصول', 'sort_order' => $index + 1,
        ]])->all();
        $article->products()->sync($sync);
    }

    private function syncGalleries(Article $article, Request $request): void
    {
        if ($removeIds = $request->input('remove_gallery_ids', [])) {
            $article->galleries()->whereIn('id', $removeIds)->delete();
        }

        foreach ($request->input('galleries', []) as $index => $galleryData) {
            $gallery = ! empty($galleryData['id'])
                ? $article->galleries()->findOrFail($galleryData['id'])
                : new ArticleGallery(['article_id' => $article->id]);
            $gallery->fill([
                'title' => trim(strip_tags($galleryData['title'])),
                'description' => trim(strip_tags($galleryData['description'] ?? '')) ?: null,
                'source_type' => $galleryData['source_type'],
                'display_style' => $galleryData['display_style'],
                'product_category_id' => $galleryData['product_category_id'] ?? null,
                'settings' => [
                    'limit' => min(24, max(1, (int) ($galleryData['limit'] ?? 12))),
                    'product_ids' => array_values(array_map('intval', $galleryData['product_ids'] ?? [])),
                    'generated_image_ids' => array_values(array_map('intval', $galleryData['generated_image_ids'] ?? [])),
                ],
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
            $gallery->save();

            foreach ($galleryData['items'] ?? [] as $itemIndex => $itemData) {
                if (! empty($itemData['id'])) {
                    $item = $gallery->items()->withoutGlobalScopes()->findOrFail($itemData['id']);
                    if (! empty($itemData['remove'])) {
                        $item->delete();
                        continue;
                    }
                } else {
                    if (blank($itemData['media_path'] ?? null)) continue;
                    $item = $gallery->items()->make();
                }
                $item->fill([
                    'media_type' => $itemData['media_type'] ?? 'image',
                    'media_path' => ltrim((string) $itemData['media_path'], '/'),
                    'title' => strip_tags((string) ($itemData['title'] ?? '')) ?: null,
                    'description' => strip_tags((string) ($itemData['description'] ?? '')) ?: null,
                    'alt_text' => strip_tags((string) ($itemData['alt_text'] ?? '')) ?: null,
                    'link_url' => $this->safeUrl($itemData['link_url'] ?? null),
                    'sort_order' => $itemIndex + 1,
                    'is_active' => true,
                ])->save();
            }

            $lastSortOrder = (int) $gallery->items()->max('sort_order');
            foreach ($request->file("galleries.{$index}.uploads", []) as $uploadIndex => $file) {
                $path = $file->store('articles/galleries', 'public');
                $gallery->items()->create([
                    'media_type' => str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image',
                    'media_path' => 'storage/' . $path,
                    'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'alt_text' => $gallery->title,
                    'sort_order' => $lastSortOrder + $uploadIndex + 1,
                    'is_active' => true,
                ]);
            }
        }
    }

    private function recordRevision(Article $article, string $action, ?string $note, Request $request): void
    {
        $version = (int) $article->revisions()->max('version') + 1;
        $article->revisions()->create([
            'admin_id' => $request->user('admin')?->id,
            'version' => $version,
            'snapshot' => collect($article->attributesToArray())->except(['created_at', 'updated_at', 'deleted_at'])->all(),
            'action' => $action,
            'change_note' => $note,
        ]);
    }

    private function syncGrowthContent(Article $article): void
    {
        if (! Schema::hasTable('growth_contents')) return;
        GrowthContent::query()->updateOrCreate(
            ['channel' => 'website', 'content_type' => 'article', 'external_id' => (string) $article->id],
            [
                'title' => $article->title,
                'external_url' => $article->publicUrl(),
                'status' => $article->isPubliclyVisible() ? 'active' : 'draft',
                'impressions' => $article->view_count,
                'comments' => $article->approvedComments()->count(),
                'published_at' => $article->published_at,
                'metrics_updated_at' => now(),
                'created_by' => $article->created_by,
            ]
        );
    }

    private function formData(Article $article): array
    {
        return [
            'article' => $article,
            'categories' => ArticleCategory::where('is_active', true)->orderBy('sort_order')->get(),
            'authors' => ArticleAuthor::where('is_active', true)->orderBy('name')->get(),
            'admins' => Admin::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'role']),
            'products' => Product::where('status', 'active')->latest()->limit(200)->get(['id', 'name_fa', 'product_code', 'thumbnail']),
            'productCategories' => Category::active()->orderBy('name_fa')->get(['id', 'name_fa', 'path']),
            'generatedImages' => Schema::hasTable('generated_images')
                ? GeneratedImage::with('product')->latest()->limit(80)->get()
                : collect(),
        ];
    }

    private function assertCanPublish(Request $request, ?Article $article = null): void
    {
        $requestedStatus = (string) $request->input('status');
        $isPublicationDecision = in_array($requestedStatus, ['published', 'scheduled'], true)
            && (! $article || $article->status !== $requestedStatus);

        if ($isPublicationDecision) {
            abort_unless($request->user('admin')?->isLeader(), 403);
        }
    }

    private function safeUrl(mixed $value): ?string
    {
        $url = trim((string) $value);
        if ($url === '') return null;
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) return $url;
        if (filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) return $url;

        return null;
    }
}
