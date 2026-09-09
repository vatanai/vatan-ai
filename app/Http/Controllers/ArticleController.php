<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleCategory;
use App\Models\ArticleRedirect;
use App\Services\ArticleAnalyticsService;
use App\Services\ArticleGalleryService;
use App\Services\SitePageService;
use App\Services\SitemapService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleAnalyticsService $analytics,
        private readonly ArticleGalleryService $galleries,
        private readonly SitePageService $pages,
        private readonly SitemapService $sitemaps,
    ) {
    }

    public function index(Request $request): View
    {
        $sitePage = $this->pages->forRoute($request->route()?->getName());
        $query = Article::query()
            ->published()
            ->with('category');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($articles) use ($search) {
                $articles->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhereJsonContains('seo_keywords', $search)
                    ->orWhereHas('tags', fn ($tags) => $tags->where('name', 'like', "%{$search}%"));
            });
        }

        $perPage = min(24, max(6, (int) ($sitePage?->content('items_per_page', 12) ?? 12)));
        $articles = $query->orderByDesc('is_featured')->latest('published_at')->paginate($perPage)->withQueryString();
        $featuredArticles = Article::published()->where('is_featured', true)->with('category')->latest('published_at')->limit(3)->get();
        $categories = ArticleCategory::query()->where('is_active', true)
            ->withCount(['articles' => fn ($articles) => $articles->published()])
            ->orderBy('sort_order')->get();

        $canonicalUrl = request()->filled('q') || $articles->currentPage() === 1
            ? route('articles.index')
            : route('articles.index', ['page' => $articles->currentPage()]);

        return view('articles.index', compact('articles', 'featuredArticles', 'categories', 'search', 'sitePage', 'canonicalUrl'));
    }

    public function category(Request $request, string $slug): View
    {
        $category = ArticleCategory::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $articles = $category->articles()->published()->with('category')->paginate(12)->withQueryString();
        $categories = ArticleCategory::query()->where('is_active', true)->withCount([
            'articles' => fn ($articles) => $articles->published(),
        ])->orderBy('sort_order')->get();

        return view('articles.archive', compact('category', 'articles', 'categories'));
    }

    public function author(string $slug): View
    {
        $author = ArticleAuthor::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $articles = $author->articles()->published()->with('category')->paginate(12);

        return view('articles.author', compact('author', 'articles'));
    }

    public function show(Request $request, string $slug): View|\Illuminate\Http\RedirectResponse
    {
        $article = Article::query()->where('slug', $slug)->first();
        $isAdminPreview = auth('admin')->check() && $request->boolean('preview');

        if (! $article) {
            $redirect = ArticleRedirect::query()->where('old_slug', $slug)->where('is_active', true)->first();
            if ($redirect) {
                $redirect->increment('hit_count');
                $destination = $redirect->target_url ?: $redirect->article?->publicUrl();
                if ($destination) return redirect()->away($destination, 301);
            }
            abort(404);
        }

        abort_unless($article->isPubliclyVisible() || $isAdminPreview, 404);

        if (! $isAdminPreview) {
            try {
                $this->analytics->record($article, 'view', $request);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $article->load([
            'category', 'author', 'tags',
            'products' => fn ($products) => $products->where('status', 'active'),
            'approvedComments.user', 'approvedComments.replies.user',
        ])->loadCount(['approvedComments as comments_count']);

        $resolvedGalleries = $this->galleries->resolve($article);
        $relatedArticles = Article::published()
            ->whereKeyNot($article->id)
            ->where(function ($related) use ($article) {
                $related->where('article_category_id', $article->article_category_id)
                    ->orWhereHas('tags', fn ($tags) => $tags->whereIn('article_tags.id', $article->tags->pluck('id')));
            })
            ->with('category')->latest('published_at')->limit(4)->get();

        return view('articles.show', compact('article', 'resolvedGalleries', 'relatedArticles', 'isAdminPreview'));
    }

    public function track(Request $request, Article $article): \Illuminate\Http\Response
    {
        abort_unless($article->isPubliclyVisible(), 404);
        $data = $request->validate([
            'event_type' => ['required', 'in:' . implode(',', ArticleAnalyticsService::EVENTS)],
            'metadata' => ['nullable', 'array'],
        ]);
        if ($data['event_type'] !== 'view') {
            $this->analytics->record($article, $data['event_type'], $request, $data['metadata'] ?? []);
        }

        return response()->noContent();
    }

    public function feed(): \Illuminate\Http\Response
    {
        $articles = Article::indexable()->with('author')->latest('published_at')->limit(50)->get();

        return response()->view('articles.feed', compact('articles'))->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    public function sitemap(): \Illuminate\Http\Response
    {
        $urls = $this->sitemaps->entries();

        return response(view()->file(resource_path('views/articles/sitemap.xml.blade.php'), compact('urls')))
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function sitemapPage(): View
    {
        return view('site.sitemap', [
            'sections' => $this->sitemaps->sections(),
        ]);
    }
}
