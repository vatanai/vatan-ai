<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TelegramProductDraft;
use App\Models\TelegramProductEvent;
use App\Models\TelegramProductManager;
use App\Models\TelegramProductSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class TelegramProductBotSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureLeader();

        $managers = TelegramProductManager::query()
            ->withCount('drafts')
            ->latest()
            ->get();
        $draftsReady = Schema::hasTable('telegram_product_drafts');
        $eventsReady = Schema::hasTable('telegram_product_events');
        $logSearch = trim((string) $request->query('log_search', ''));
        $logDate = trim((string) $request->query('log_date', ''));
        $drafts = collect();
        if ($draftsReady) {
            $draftQuery = TelegramProductDraft::query()->with(['manager', 'product']);

            if ($logSearch !== '') {
                $draftQuery->where(function ($query) use ($logSearch): void {
                    $query
                        ->where('description', 'like', '%' . $logSearch . '%')
                        ->orWhereHas('manager', function ($managerQuery) use ($logSearch): void {
                            $managerQuery->where('name', 'like', '%' . $logSearch . '%');
                        })
                        ->orWhereHas('product', function ($productQuery) use ($logSearch): void {
                            $productQuery
                                ->where('name_fa', 'like', '%' . $logSearch . '%')
                                ->orWhere('name_en', 'like', '%' . $logSearch . '%')
                                ->orWhere('product_code', 'like', '%' . $logSearch . '%');
                        });
                });
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $logDate) === 1) {
                $draftQuery->whereDate('created_at', $logDate);
            }

            $drafts = $draftQuery->latest()->paginate(30)->withQueryString();
        }
        $stats = [
            'managers' => $managers->count(),
            'active_managers' => $managers->where('is_active', true)->count(),
            'active_drafts' => $draftsReady
                ? TelegramProductDraft::query()->whereIn('state', TelegramProductDraft::ACTIVE_STATES)->count()
                : 0,
            'events' => $eventsReady ? TelegramProductEvent::query()->count() : 0,
        ];

        return view('admin.settings.telegram-product-bot', [
            'managers' => $managers,
            'drafts' => $drafts,
            'logSearch' => $logSearch,
            'logDate' => $logDate,
            'stats' => $stats,
            'metadataPrompt' => TelegramProductSetting::value('metadata_prompt', ''),
            'botToken' => $this->maskedToken((string) config('services.telegram_product.bot_token')),
            'webhookSecret' => $this->maskedToken((string) config('services.telegram_product.webhook_secret')),
            'maxImages' => (int) config('services.telegram_product.max_images', 5),
            'aiModel' => (string) config('services.telegram_product.ai_model', 'تنظیم نشده'),
            'webhookUrl' => route('telegram.product.webhook'),
        ]);
    }

    public function updateMasterPrompt(Request $request): RedirectResponse
    {
        $this->ensureLeader();

        $data = $request->validate([
            'metadata_prompt' => ['required', 'string', 'min:30', 'max:12000'],
        ]);

        TelegramProductSetting::put('metadata_prompt', $data['metadata_prompt']);

        return back()->with('success', 'پرامپت مادر با موفقیت ذخیره شد و از ثبت بعدی درجا اعمال می‌شود.');
    }

    public function storeManager(Request $request): RedirectResponse
    {
        $this->ensureLeader();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'telegram_id' => ['required', 'integer', 'min:1', 'unique:telegram_product_managers,telegram_id'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        TelegramProductManager::query()->create($data);

        return back()->with('success', 'مدیر بات ثبت محصول اضافه شد.');
    }

    public function updateManager(Request $request, TelegramProductManager $manager): RedirectResponse
    {
        $this->ensureLeader();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'telegram_id' => ['required', 'integer', 'min:1', 'unique:telegram_product_managers,telegram_id,' . $manager->id],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $manager->update($data);

        return back()->with('success', 'تنظیمات مدیر بات ثبت محصول به‌روزرسانی شد.');
    }

    private function ensureLeader(): void
    {
        abort_unless(auth('admin')->user()?->isLeader(), 403);
    }

    private function maskedToken(string $token): string
    {
        if ($token === '') {
            return 'تنظیم نشده';
        }

        return '••••••••••••' . substr($token, -4);
    }
}
