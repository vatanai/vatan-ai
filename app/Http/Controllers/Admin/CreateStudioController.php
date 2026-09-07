<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\AiProviderRequest;
use App\Models\GeneratedImage;
use App\Models\GeneratedVideo;
use App\Models\Order;
use App\Models\Product;
use App\Models\StudioCostRule;
use App\Models\StudioPricingSetting;
use App\Services\StudioCostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CreateStudioController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with(['user', 'product'])
            ->where('source', 'app')
            ->latest()
            ->limit(100)
            ->get();

        $recentGenerations = $this->recentGenerations();
        $providerRequests = AiProviderRequest::query()->latest()->limit(100)->get();
        $totalOrders = (int) Order::query()->where('source', 'app')->count();
        $completedOrders = (int) Order::query()->where('source', 'app')->whereIn('status', ['completed', 'success'])->count();
        $studioCosts = app(StudioCostService::class);
        $imageModels = AiModel::query()->where('is_active', true)->where('output_modality', 'image')->orderByDesc('lab_priority')->orderBy('name')->get();
        $videoModels = AiModel::query()->where('is_active', true)->where('output_modality', 'video')->orderByDesc('lab_priority')->orderBy('name')->get();
        $pricedModels = $imageModels->merge($videoModels)->map(function (AiModel $model) use ($studioCosts): array {
            $mediaType = $model->output_modality === 'video' ? 'video' : 'image';
            $price = $studioCosts->modelUnitPrice($model, $mediaType, $mediaType === 'video' ? '720p' : '2K', $mediaType === 'video' ? 4 : null);
            return [
                'name' => $model->name ?: $model->openrouter_model_id,
                'provider' => $model->provider,
                'media_type' => $mediaType,
                'price_usd' => $price,
                'pricing_source' => data_get($model->pricing_config, 'source', 'کاتالوگ مدل'),
                'cost_known' => $price !== null && $price > 0,
            ];
        })->values();
        $providerCoverage = $pricedModels->groupBy('provider')->map(fn (Collection $models): array => [
            'total' => $models->count(),
            'priced' => $models->where('cost_known', true)->count(),
            'missing' => $models->where('cost_known', false)->count(),
        ]);

        return view('admin.create-studio.index', [
            'orders' => $orders,
            'recentGenerations' => $recentGenerations,
            'costRules' => Schema::hasTable('studio_cost_rules') ? StudioCostRule::query()->with(['product', 'aiModel'])->latest()->get() : collect(),
            'products' => Product::query()->where('status', 'active')->orderBy('name_fa')->get(),
            'imageModels' => $imageModels,
            'videoModels' => $videoModels,
            'pricingSettings' => StudioPricingSetting::query()->firstOrCreate(['id' => 1], ['image_profit_percent' => 10, 'video_profit_percent' => 10]),
            'pricedModels' => $pricedModels,
            'providerCoverage' => $providerCoverage,
            'stats' => [
                'orders' => $totalOrders,
                'completed' => $completedOrders,
                'images' => GeneratedImage::query()->count(),
                'videos' => Schema::hasTable('generated_videos') ? GeneratedVideo::query()->count() : 0,
                'credits' => (int) Order::query()->where('source', 'app')->sum('final_credits'),
                'usd' => round($providerRequests->sum(fn (AiProviderRequest $item): float => (float) ($item->actual_cost_usd ?? $item->estimated_cost_usd ?? 0)), 6),
                'success_rate' => $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0,
            ],
            'tab' => in_array($request->query('tab'), ['generations', 'costs', 'credits'], true) ? $request->query('tab') : 'overview',
        ]);
    }

    public function updatePricingSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'image_profit_percent' => ['required', 'numeric', 'min:0', 'max:1000'],
            'video_profit_percent' => ['required', 'numeric', 'min:0', 'max:1000'],
        ]);

        StudioPricingSetting::query()->updateOrCreate(['id' => 1], $data);

        return back()->with('success', 'درصد سود عکس و ویدیو ذخیره شد؛ قیمت ساخت بعدی خودکار محاسبه می‌شود.');
    }

    public function storeCostRule(Request $request): RedirectResponse
    {
        StudioCostRule::create($this->validatedRule($request));

        return back()->with('success', 'قانون هزینه‌ی ساخت با موفقیت ثبت شد.');
    }

    public function updateCostRule(Request $request, StudioCostRule $studioCostRule): RedirectResponse
    {
        $studioCostRule->update($this->validatedRule($request));

        return back()->with('success', 'قانون هزینه‌ی ساخت به‌روزرسانی شد.');
    }

    public function destroyCostRule(StudioCostRule $studioCostRule): RedirectResponse
    {
        $studioCostRule->delete();

        return back()->with('success', 'قانون هزینه‌ی ساخت حذف شد.');
    }

    private function validatedRule(Request $request): array
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'ai_model_id' => ['nullable', 'exists:ai_models,id'],
            'media_type' => ['required', 'in:image,video'],
            'provider' => ['nullable', 'string', 'max:40'],
            'resolution' => ['nullable', 'string', 'max:30'],
            'aspect_ratio' => ['nullable', 'string', 'max:20'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:15'],
            'base_cost_usd' => ['required', 'numeric', 'min:0'],
            'exchange_rate_toman' => ['nullable', 'numeric', 'min:0'],
            'cost_toman' => ['nullable', 'numeric', 'min:0'],
            'profit_value' => ['required', 'numeric', 'min:0', 'max:10000'],
            'credit_cost' => ['required', 'integer', 'min:1'],
        ]);
        $data['profit_type'] = 'percentage';
        $data['cost_toman'] = null;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function recentGenerations(): Collection
    {
        $images = GeneratedImage::query()->with(['user', 'product', 'order', 'providerRequest.aiModel'])->latest()->limit(60)->get()
            ->map(fn (GeneratedImage $item): array => [
                'id' => $item->id,
                'media_type' => 'image',
                'media_label' => 'عکس',
                'user' => $item->user?->name ?: $item->user?->mobile ?: 'کاربر حذف‌شده',
                'product' => $item->product?->name_fa ?: 'بدون محصول',
                'model' => $item->providerRequest?->aiModel?->name ?: ($item->order?->ai_model ?: '—'),
                'provider' => $item->providerRequest?->provider ?: ($item->order?->ai_provider ?: '—'),
                'status' => 'completed',
                'credits' => (int) ($item->order?->final_credits ?? 0),
                'usd' => (float) ($item->providerRequest?->actual_cost_usd ?? $item->providerRequest?->estimated_cost_usd ?? $item->cost ?? 0),
                'attempts' => (int) ($item->order?->attempts ?? 1),
                'is_sample' => false,
                'created_at' => $item->created_at,
            ]);

        $videos = Schema::hasTable('generated_videos')
            ? GeneratedVideo::query()->with(['user', 'product', 'order', 'providerRequest.aiModel'])->latest()->limit(60)->get()
            ->map(fn (GeneratedVideo $item): array => [
                'id' => $item->id,
                'media_type' => 'video',
                'media_label' => 'ویدیو',
                'user' => $item->user?->name ?: $item->user?->mobile ?: 'کاربر حذف‌شده',
                'product' => $item->product?->name_fa ?: 'بدون محصول',
                'model' => $item->providerRequest?->aiModel?->name ?: ($item->order?->ai_model ?: '—'),
                'provider' => $item->providerRequest?->provider ?: ($item->order?->ai_provider ?: '—'),
                'status' => $item->status,
                'credits' => (int) ($item->order?->final_credits ?? 0),
                'usd' => (float) ($item->providerRequest?->actual_cost_usd ?? $item->providerRequest?->estimated_cost_usd ?? $item->cost ?? 0),
                'attempts' => (int) ($item->order?->attempts ?? 1),
                'is_sample' => false,
                'created_at' => $item->created_at,
            ])
            : collect();

        $generations = $images->concat($videos)->sortByDesc('created_at')->take(80)->values();

        return $generations->isNotEmpty() ? $generations : collect([
            [
                'id' => 'نمونه-۱', 'media_type' => 'image', 'media_label' => 'عکس',
                'user' => 'نمونه کاربر', 'product' => 'پرتره فشن هوش مصنوعی',
                'model' => 'مدل اصلی تصویر', 'provider' => 'openrouter', 'status' => 'completed',
                'credits' => 18, 'usd' => 0.025, 'created_at' => now(), 'is_sample' => true,
            ],
            [
                'id' => 'نمونه-۲', 'media_type' => 'video', 'media_label' => 'ویدیو',
                'user' => 'نمونه کاربر', 'product' => 'ویدیوی سینمایی کوتاه',
                'model' => 'مدل اصلی ویدیو', 'provider' => 'fal', 'status' => 'processing',
                'credits' => 42, 'usd' => 0.18, 'created_at' => now()->subMinutes(8), 'is_sample' => true,
            ],
            [
                'id' => 'نمونه-۳', 'media_type' => 'image', 'media_label' => 'عکس',
                'user' => 'کاربر نمونه', 'product' => 'پرتره فشن هوش مصنوعی',
                'model' => 'مدل جایگزین تصویر', 'provider' => 'openrouter', 'status' => 'completed',
                'credits' => 24, 'usd' => 0.035, 'created_at' => now()->subMinutes(22), 'is_sample' => true,
            ],
        ]);
    }
}
