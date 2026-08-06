<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePlanRequest;
use App\Models\Plan;
use App\Models\PlanSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(Request $request): View
    {
        $query = Plan::query()->withCount('purchases')->orderBy('sort_order')->orderBy('id');

        if ($request->filled('status')) {
            $request->status === 'archived'
                ? $query->whereNotNull('archived_at')
                : $query->whereNull('archived_at')->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('plan_code', 'like', "%{$search}%"));
        }

        return view('admin.plans.index', [
            'plans' => $query->get(),
            'display' => PlanSetting::display(),
            'stats' => [
                'total' => Plan::count(),
                'active' => Plan::published()->count(),
                'draft' => Plan::where('status', 'draft')->whereNull('archived_at')->count(),
                'purchases' => DB::table('plan_purchases')->where('status', 'completed')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.plans.create', ['plan' => new Plan()]);
    }

    public function store(SavePlanRequest $request): RedirectResponse
    {
        $plan = DB::transaction(fn () => Plan::create($this->payload($request)));

        return redirect()->route('admin.plans.edit', $plan)->with('success', 'پلن با موفقیت ساخته شد.');
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(SavePlanRequest $request, Plan $plan): RedirectResponse
    {
        DB::transaction(function () use ($request, $plan) {
            $payload = $this->payload($request, $plan);
            $payload['version'] = ((int) $plan->version) + 1;
            $plan->update($payload);
        });

        return back()->with('success', 'تغییرات پلن ذخیره شد.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->purchases()->exists()) {
            return back()->with('error', 'این پلن سابقه خرید دارد و قابل حذف نیست؛ آن را آرشیو کنید.');
        }

        if ($plan->image_path) {
            Storage::disk('public')->delete($plan->image_path);
        }
        $plan->delete();

        return back()->with('success', 'پلن حذف شد.');
    }

    public function duplicate(Plan $plan): RedirectResponse
    {
        $copy = $plan->replicate(['plan_code', 'archived_at']);
        $copy->name = $plan->name . ' - کپی';
        $copy->slug = $this->uniqueSlug($plan->slug . '-copy');
        $copy->status = 'draft';
        $copy->is_featured = false;
        $copy->version = 1;
        $copy->save();

        return redirect()->route('admin.plans.edit', $copy)->with('success', 'یک نسخه پیش‌نویس از پلن ساخته شد.');
    }

    public function archive(Plan $plan): RedirectResponse
    {
        $plan->update(['archived_at' => $plan->archived_at ? null : now()]);

        return back()->with('success', $plan->archived_at ? 'پلن آرشیو شد.' : 'پلن از آرشیو خارج شد.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer', 'exists:plans,id']]);
        foreach (array_values($data['order']) as $index => $id) {
            Plan::whereKey($id)->update(['sort_order' => $index + 1]);
        }

        return back()->with('success', 'ترتیب نمایش پلن‌ها ذخیره شد.');
    }

    public function updateDisplay(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'in:cards,comparison'],
            'home_limit' => ['required', 'integer', 'between:1,6'],
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:300'],
        ]);
        $data['show_images'] = $request->boolean('show_images');
        $data['show_comparison'] = $request->boolean('show_comparison');

        PlanSetting::updateOrCreate(['key' => 'display'], ['value' => $data]);

        return back()->with('success', 'تنظیمات نمایش ذخیره شد.');
    }

    private function payload(SavePlanRequest $request, ?Plan $plan = null): array
    {
        $data = $request->validated();
        $features = collect($data['features'])->values()->map(fn ($feature, $index) => [
            'title' => trim($feature['title']),
            'value' => trim((string) ($feature['value'] ?? '')),
            'included' => $feature['included'],
            'highlighted' => (bool) ($feature['highlighted'] ?? false),
            'sort_order' => $index + 1,
        ])->all();

        $imagePath = $plan?->image_path;
        if ($request->hasFile('image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('plans', 'public');
        }

        return [
            'name' => $data['name'],
            'slug' => Str::slug($data['slug']),
            'price' => $data['price'],
            'price_prefix' => $data['price_prefix'] ?? null,
            'compare_at_price' => $data['compare_at_price'] ?? null,
            'tokens' => $data['tokens'],
            'token_label' => $data['token_label'] ?? null,
            'billing_type' => $data['billing_type'],
            'is_unlimited' => $data['is_unlimited'],
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?: 'fa-solid fa-gem',
            'card_style' => $data['card_style'],
            'badge_text' => $data['badge_text'] ?? null,
            'features' => $features,
            'audience_overrides' => [
                'loyal' => [
                    'price' => $data['loyal_price'] ?? null,
                    'tokens' => $data['loyal_tokens'] ?? null,
                    'bonus_tokens' => $data['loyal_bonus_tokens'] ?? 0,
                    'visible' => $data['loyal_visible'],
                    'purchasable' => $data['loyal_purchasable'],
                ],
            ],
            'sort_order' => $data['sort_order'],
            'status' => $data['status'],
            'is_featured' => $data['is_featured'],
            'purchase_limit' => $data['purchase_limit'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'image_path' => $imagePath,
        ];
    }

    private function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base);
        $candidate = $slug;
        $number = 2;
        while (Plan::where('slug', $candidate)->exists()) {
            $candidate = $slug . '-' . $number++;
        }
        return $candidate;
    }
}
