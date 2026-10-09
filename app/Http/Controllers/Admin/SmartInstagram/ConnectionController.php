<?php

namespace App\Http\Controllers\Admin\SmartInstagram;

use App\Models\Admin;
use App\Models\MarketingIntegration;
use App\Models\SmartInstagram\Channel;
use App\Models\SmartInstagram\TelegramAdmin;
use App\Services\SmartInstagram\Telegram\InstagramTelegramBot;
use App\Models\SmartInstagram\WorkspaceMember;
use App\Services\SmartInstagram\ChannelService;
use App\Services\SmartInstagram\Gateways\GatewayManager;
use App\Services\SmartInstagram\OperationLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/** مرکز اتصال‌ها، تیم و تنظیمات فضای کاری (پروپوزال ۴ و ۵.۱). */
class ConnectionController extends Controller
{
    public function index(GatewayManager $gateways): View
    {
        $this->authorizeAbility('view');
        $workspace = $this->context->workspace();

        return view('admin.smart-instagram.connections', [
            'workspace' => $workspace,
            'channels' => Channel::query()->where('workspace_id', $this->ws())->with('integration:id,name,status,last_checked_at,last_success_at,last_error')->orderBy('id')->get(),
            'metaIntegration' => Schema::hasTable('marketing_integrations') ? MarketingIntegration::query()->where('provider', 'meta')->first(['id', 'name', 'status', 'last_checked_at', 'last_success_at', 'last_error']) : null,
            'composioConfigured' => (bool) config('smart_instagram.composio.enabled') && filled(config('services.composio.connected_account_id')) && filled(config('services.composio.user_id')),
            'metaIntegrationsUrl' => Route::has('admin.marketing-technology.integrations') ? route('admin.marketing-technology.integrations') : null,
            'gateways' => $gateways->available(),
            'members' => WorkspaceMember::query()->where('workspace_id', $this->ws())->with('admin:id,name,email,role')->get(),
            'admins' => Admin::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'role']),
            'roles' => config('smart_instagram.roles'),
            'checks' => [
                'outbound' => (bool) config('smart_instagram.outbound_enabled'),
                'meta_app' => filled(config('services.meta.app_id')) && filled(config('services.meta.app_secret')),
                'meta_verify' => filled(config('services.meta.webhook_verify_token')),
                'composio' => (bool) config('smart_instagram.composio.enabled') && filled(config('services.composio.connected_account_id')) && filled(config('services.composio.user_id')),
                'ingest_secret' => filled(config('smart_instagram.ingest.secret')),
                'ai' => (bool) config('smart_instagram.ai.enabled') && filled(config('services.openrouter.api_key')),
                'transcription' => (bool) config('smart_instagram.media.transcription_enabled'),
            ],
            'urls' => [
                'meta_webhook' => Route::has('webhooks.meta.verify') ? route('webhooks.meta.verify') : url('/webhooks/meta'),
                'ingest' => route('webhooks.smart-instagram.ingest'),
            ],
            'telegramBot' => ['configured' => app(InstagramTelegramBot::class)->configured(), 'username' => app(InstagramTelegramBot::class)->username()],
            'telegramAccounts' => Schema::hasTable('instagram_telegram_admins')
                ? TelegramAdmin::query()->where('workspace_id', $this->ws())
                    ->when(!$this->context->can($this->admin(), 'manage_settings'), fn ($q) => $q->where('admin_id', $this->admin()?->id))
                    ->with('admin:id,name,is_active')->orderBy('id')->get()
                : collect(),
            'telegramRoles' => TelegramAdmin::ROLES,
            'canManage' => $this->context->can($this->admin(), 'manage_settings'),
            'myRole' => $this->context->role($this->admin()),
        ]);
    }

    /** لینک یک‌بارمصرف اتصال حساب تلگرام ادمین فعلی به بات «ثبت پست». */
    public function telegramLink(InstagramTelegramBot $bot): RedirectResponse
    {
        $this->authorizeAbility('manage_automation');
        abort_unless($bot->configured(), 422, 'توکن بات تلگرام تنظیم نشده است.');

        return redirect()->away($bot->linkUrl($this->admin()));
    }

    public function telegramUnlink(TelegramAdmin $account): RedirectResponse
    {
        abort_unless((int) $account->workspace_id === $this->ws(), 404);
        abort_unless($this->context->can($this->admin(), 'manage_settings') || (int) $account->admin_id === (int) $this->admin()?->id, 403);
        $account->delete();

        return back()->with('success', 'دسترسی این حساب تلگرام به بات حذف شد.');
    }

    /** افزودن کارمند به فهرست مجاز بات با شناسه‌ی عددی تلگرام. */
    public function telegramStore(Request $request): RedirectResponse
    {
        $this->authorizeAbility('manage_settings');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'telegram_id' => ['required', 'string', 'regex:/^\s*[0-9۰-۹]{5,15}\s*$/u'],
            'role' => ['required', 'in:'.implode(',', array_keys(TelegramAdmin::ROLES))],
        ], ['telegram_id.regex' => 'شناسه‌ی تلگرام باید فقط عدد باشد (مثلاً 101754869).'], ['telegram_id' => 'شناسه‌ی تلگرام', 'name' => 'نام']);
        $telegramId = (int) strtr(trim($data['telegram_id']), ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']);
        $existing = TelegramAdmin::query()->where('telegram_id', $telegramId)->first();
        if ($existing && (int) $existing->workspace_id !== $this->ws()) {
            return back()->withErrors(['telegram_id' => 'این شناسه قبلاً ثبت شده است.']);
        }
        TelegramAdmin::query()->updateOrCreate(['telegram_id' => $telegramId], [
            'workspace_id' => $this->ws(), 'name' => trim($data['name']), 'role' => $data['role'],
            'is_active' => true, 'added_by' => $this->admin()?->id,
        ]);

        return back()->with('success', '«'.trim($data['name']).'» به کارمندان بات اضافه شد؛ کافی است یک بار بات را Start کند.');
    }

    public function telegramUpdate(Request $request, TelegramAdmin $account): RedirectResponse
    {
        $this->authorizeAbility('manage_settings');
        abort_unless((int) $account->workspace_id === $this->ws(), 404);
        $data = $request->validate([
            'role' => ['nullable', 'in:'.implode(',', array_keys(TelegramAdmin::ROLES))],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $account->forceFill(array_filter($data, fn ($v) => $v !== null))->save();

        return back()->with('success', 'دسترسی «'.$account->displayName().'» به‌روز شد.');
    }

    public function sync(ChannelService $channels): RedirectResponse
    {
        $this->authorizeAbility('manage_settings');
        $channel = $channels->syncFromMetaIntegration();

        return back()->with($channel ? 'success' : 'warning', $channel ? 'کانال با اتصال Meta هم‌گام شد.' : 'هنوز اتصال Meta در «تکنولوژی مارکتینگ › اتصال‌ها» ثبت نشده است.');
    }

    public function syncComposio(ChannelService $channels): RedirectResponse
    {
        $this->authorizeAbility('manage_settings');
        $channel = $channels->syncFromComposio();
        if (!$channel) {
            return back()->with('warning', 'تنظیمات اتصال `Composio` روی این محیط کامل نشده است.');
        }

        $result = $channels->testHealth($channel);

        return back()->with($result['ok'] ? 'success' : 'error', $result['ok'] ? 'اینستاگرام وطن با `Composio` هم‌گام و بررسی شد.' : $result['message']);
    }

    public function test(Channel $channel, ChannelService $channels): RedirectResponse
    {
        $this->authorizeAbility('manage_settings');
        $this->own($channel);
        $result = $channels->testHealth($channel);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function storeSandbox(OperationLogger $logger): RedirectResponse
    {
        $this->authorizeAbility('manage_settings');
        $channel = Channel::query()->firstOrCreate(
            ['workspace_id' => $this->ws(), 'gateway' => 'sandbox'],
            ['name' => 'کانال آزمایشی', 'username' => 'vatan_sandbox', 'external_account_id' => 'sandbox', 'status' => 'connected', 'outbound_enabled' => false]
        );
        $logger->log('channel.sandbox', 'کانال آزمایشی آماده شد.', $channel);

        return back()->with('success', 'کانال آزمایشی آماده است؛ ارسال‌های آن هیچ تماس بیرونی ندارند.');
    }

    public function update(Request $request, Channel $channel, OperationLogger $logger): RedirectResponse
    {
        $this->authorizeAbility('manage_settings');
        $this->own($channel);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'outbound_enabled' => ['nullable', 'boolean'],
        ]);
        $channel->forceFill(['name' => $data['name'], 'outbound_enabled' => $request->boolean('outbound_enabled')])->save();
        $logger->log('channel.updated', 'تنظیمات کانال «'.$channel->name.'» ذخیره شد (ارسال: '.($channel->outbound_enabled ? 'روشن' : 'خاموش').').', $channel, [], 'warning');

        return back()->with('success', 'تنظیمات کانال ذخیره شد.');
    }

    public function settings(Request $request, OperationLogger $logger): RedirectResponse
    {
        $this->authorizeAbility('manage_settings');
        $data = $request->validate([
            'business_hours_start' => ['required', 'date_format:H:i'],
            'business_hours_end' => ['required', 'date_format:H:i', 'after:business_hours_start'],
        ]);
        $workspace = $this->context->workspace();
        $settings = (array) $workspace->settings;
        $settings['business_hours'] = ['start' => $data['business_hours_start'], 'end' => $data['business_hours_end']];
        $workspace->forceFill(['settings' => $settings])->save();
        $logger->log('workspace.settings', 'ساعت کاری به‌روز شد.', $workspace);

        return back()->with('success', 'ساعت کاری ذخیره شد.');
    }

    public function storeMember(Request $request, OperationLogger $logger): RedirectResponse
    {
        $this->authorizeAbility('manage_settings');
        $data = $request->validate([
            'admin_id' => ['required', 'integer', 'exists:admins,id'],
            'role' => ['required', 'in:'.implode(',', array_keys(config('smart_instagram.roles')))],
        ]);
        $member = WorkspaceMember::query()->updateOrCreate(
            ['workspace_id' => $this->ws(), 'admin_id' => $data['admin_id']],
            ['role' => $data['role'], 'is_active' => true]
        );
        $logger->log('team.role', 'نقش «'.config('smart_instagram.roles')[$data['role']].'» برای ادمین #'.$data['admin_id'].' ثبت شد.', $member, [], 'warning');

        return back()->with('success', 'دسترسی عضو ذخیره شد.');
    }

    public function destroyMember(WorkspaceMember $member, OperationLogger $logger): RedirectResponse
    {
        $this->authorizeAbility('manage_settings');
        abort_unless((int) $member->workspace_id === $this->ws(), 404);
        $member->delete();
        $logger->log('team.removed', 'عضو #'.$member->admin_id.' از تیم اینستاگرام حذف شد.', null, [], 'warning');

        return back()->with('success', 'عضو حذف شد و به نقش پیش‌فرض برگشت.');
    }

    private function own(Channel $channel): void
    {
        abort_unless((int) $channel->workspace_id === $this->ws(), 404);
    }
}
