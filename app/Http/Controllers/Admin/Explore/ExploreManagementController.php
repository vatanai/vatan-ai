<?php

namespace App\Http\Controllers\Admin\Explore;

use App\Http\Controllers\Controller;
use App\Models\FeedCampaign;
use App\Models\FeedContentItem;
use App\Models\FeedContentScore;
use App\Models\FeedPinnedItem;
use App\Models\FeedSetting;
use App\Models\Product;
use App\Services\Explore\ExploreFeedService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * مدیریت اکسپلور — یک بخش کاملاً مجزا در ادمین.
 * هیچ فایل/Controller بخش‌های دیگر (محصولات، سفارشات، ...) از اینجا لمس نمی‌شود.
 */
class ExploreManagementController extends Controller
{
    protected string $surfaceKey = 'explore';

    public function __construct(protected ExploreFeedService $feed)
    {
    }

    public function index()
    {
        $surface = $this->feed->getOrCreateSurface($this->surfaceKey, 'اکسپلور');
        $setting = $this->feed->getOrCreateActiveSetting($surface);

        $campaigns = FeedCampaign::query()->latest()->get();

        $pins = FeedPinnedItem::with('contentItem.content')
            ->where('feed_surface_id', $surface->id)
            ->orderBy('position')
            ->get();

        $boostedItems = FeedContentScore::with('contentItem.content')
            ->where('feed_surface_id', $surface->id)
            ->where('manual_boost', '>', 0)
            ->orderByDesc('manual_boost')
            ->get();

        $products = Product::query()
            ->where('status', 'active')
            ->orderBy('name_fa')
            ->get(['id', 'name_fa', 'slug', 'thumbnail', 'cover', 'sample_outputs']);

        return view('admin.explore.index', [
            'surface' => $surface,
            'setting' => $setting,
            'presets' => FeedSetting::LAYOUT_PRESETS,
            'campaigns' => $campaigns,
            'pins' => $pins,
            'boostedItems' => $boostedItems,
            'products' => $products,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $surface = $this->feed->getOrCreateSurface($this->surfaceKey, 'اکسپلور');

        $data = $request->validate([
            'layout_style' => 'required|in:classic,dense,magazine,custom',
            'randomness_level' => 'required|integer|min:0|max:100',
            'campaign_ratio' => 'required|integer|min:0|max:100',
            'tile_1x1' => 'required_if:layout_style,custom|nullable|integer|min:0|max:100',
            'tile_wide' => 'required_if:layout_style,custom|nullable|integer|min:0|max:100',
            'tile_tall' => 'required_if:layout_style,custom|nullable|integer|min:0|max:100',
            'tile_big' => 'required_if:layout_style,custom|nullable|integer|min:0|max:100',
        ]);

        if ($data['layout_style'] === 'custom') {
            $tileWeights = [
                'size-1x1' => (int) $data['tile_1x1'],
                'size-wide' => (int) $data['tile_wide'],
                'size-tall' => (int) $data['tile_tall'],
                'size-big' => (int) $data['tile_big'],
            ];
            if (array_sum($tileWeights) <= 0) {
                return back()->withErrors(['tile_1x1' => 'جمع وزن‌های سبک سفارشی نمی‌تواند صفر باشد.'])->withInput();
            }
        } else {
            $tileWeights = FeedSetting::LAYOUT_PRESETS[$data['layout_style']];
        }

        // نسخه‌ی قبلی را غیرفعال و نسخه‌ی جدید را فعال می‌کنیم (تاریخچه حفظ می‌شود)
        FeedSetting::where('feed_surface_id', $surface->id)
            ->where('is_active_version', true)
            ->update(['is_active_version' => false]);

        FeedSetting::create([
            'feed_surface_id' => $surface->id,
            'layout_style' => $data['layout_style'],
            'tile_weights' => $tileWeights,
            'randomness_level' => $data['randomness_level'],
            'campaign_ratio' => $data['campaign_ratio'],
            'is_active_version' => true,
        ]);

        return back()->with('success', 'تنظیمات نمایش اکسپلور بروزرسانی شد.');
    }

    public function storeCampaign(Request $request)
    {
        $data = $request->validate([
            'title_fa' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
            'link' => 'nullable|string|max:2048',
            'weight' => 'required|integer|min:1|max:100',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
        ]);

        $data['image'] = $request->file('image')->store('explore/campaigns', 'public');
        $data['is_active'] = true;

        FeedCampaign::create($data);

        return back()->with('success', 'کمپین جدید با موفقیت ثبت شد.');
    }

    public function updateCampaign(Request $request, FeedCampaign $campaign)
    {
        $data = $request->validate([
            'title_fa' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'link' => 'nullable|string|max:2048',
            'weight' => 'required|integer|min:1|max:100',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
        ]);

        if ($request->hasFile('image')) {
            if ($campaign->image) {
                Storage::disk('public')->delete($campaign->image);
            }
            $data['image'] = $request->file('image')->store('explore/campaigns', 'public');
        }

        $campaign->update($data);

        return back()->with('success', 'کمپین بروزرسانی شد.');
    }

    public function toggleCampaign(FeedCampaign $campaign)
    {
        $campaign->update(['is_active' => ! $campaign->is_active]);

        return back()->with('success', 'وضعیت کمپین تغییر کرد.');
    }

    public function destroyCampaign(FeedCampaign $campaign)
    {
        if ($campaign->image) {
            Storage::disk('public')->delete($campaign->image);
        }
        $campaign->delete();

        return back()->with('success', 'کمپین حذف شد.');
    }

    public function storePin(Request $request)
    {
        $surface = $this->feed->getOrCreateSurface($this->surfaceKey, 'اکسپلور');

        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'position' => 'required|integer|min:1|max:100',
        ]);

        $contentItem = $this->feed->ensureContentItem('product', (int) $data['product_id']);

        FeedPinnedItem::updateOrCreate(
            ['feed_surface_id' => $surface->id, 'position' => $data['position']],
            ['feed_content_item_id' => $contentItem->id]
        );

        return back()->with('success', 'محصول در موقعیت انتخاب‌شده سنجاق شد.');
    }

    public function destroyPin(FeedPinnedItem $pin)
    {
        $pin->delete();

        return back()->with('success', 'سنجاق حذف شد.');
    }

    public function updateBoost(Request $request)
    {
        $surface = $this->feed->getOrCreateSurface($this->surfaceKey, 'اکسپلور');

        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'manual_boost' => 'required|integer|min:0|max:100',
        ]);

        $contentItem = $this->feed->ensureContentItem('product', (int) $data['product_id']);

        FeedContentScore::updateOrCreate(
            ['feed_content_item_id' => $contentItem->id, 'feed_surface_id' => $surface->id],
            ['manual_boost' => $data['manual_boost'], 'computed_at' => now()]
        );

        return back()->with('success', 'امتیاز Boost محصول ذخیره شد.');
    }
}
