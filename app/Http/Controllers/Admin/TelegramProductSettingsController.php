<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TelegramProductManager;
use App\Models\TelegramProductRegistration;
use App\Models\TelegramProductSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TelegramProductSettingsController extends Controller
{
    public function index(): View
    {
        $this->ensureLeader();
        $managers = TelegramProductManager::query()->with('admin')->latest()->get();
        $registrations = TelegramProductRegistration::query()->with(['manager', 'product'])->latest()->limit(100)->get();
        $counts = $registrations->groupBy('telegram_product_manager_id')->map->count();

        return view('admin.products.settings', [
            'managers' => $managers,
            'registrations' => $registrations,
            'counts' => $counts,
            'metadataPrompt' => TelegramProductSetting::value('metadata_prompt', ''),
            'optimizerPrompt' => TelegramProductSetting::value('prompt_optimizer', ''),
            'botUsername' => config('services.telegram_product.bot_username', env('TELEGRAM_PRODUCT_BOT_USERNAME')),
            'botToken' => $this->maskedToken((string) config('services.telegram_product.bot_token')),
        ]);
    }

    public function updatePrompts(Request $request): RedirectResponse
    {
        $this->ensureLeader();
        $data = $request->validate([
            'metadata_prompt' => ['required', 'string', 'min:30', 'max:12000'],
            'prompt_optimizer' => ['required', 'string', 'min:30', 'max:12000'],
        ]);
        TelegramProductSetting::put('metadata_prompt', $data['metadata_prompt']);
        TelegramProductSetting::put('prompt_optimizer', $data['prompt_optimizer']);
        return back()->with('success', 'پرامپت‌های بات با موفقیت ذخیره شدند.');
    }

    public function storeManager(Request $request): RedirectResponse
    {
        $this->ensureLeader();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'telegram_id' => ['required', 'integer', 'min:1', 'unique:telegram_product_managers,telegram_id'],
            'is_active' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['permissions'] = $this->permissions($data['permissions'] ?? []);
        TelegramProductManager::query()->create($data);
        return back()->with('success', 'مدیر بات اضافه شد.');
    }

    public function updateManager(Request $request, TelegramProductManager $manager): RedirectResponse
    {
        $this->ensureLeader();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'telegram_id' => ['required', 'integer', 'min:1', 'unique:telegram_product_managers,telegram_id,' . $manager->id],
            'is_active' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['permissions'] = $this->permissions($data['permissions'] ?? []);
        $manager->update($data);
        return back()->with('success', 'دسترسی مدیر بات به‌روزرسانی شد.');
    }

    private function permissions(array $permissions): array
    {
        $allowed = array_keys(TelegramProductManager::defaultPermissions());
        return collect($allowed)->mapWithKeys(fn (string $key) => [$key => (bool) ($permissions[$key] ?? false)])->all();
    }

    private function maskedToken(string $token): string
    {
        if ($token === '') return 'تنظیم نشده';
        return '••••••••••••' . substr($token, -4);
    }

    private function ensureLeader(): void
    {
        abort_unless(auth('admin')->user()?->isLeader(), 403);
    }
}
