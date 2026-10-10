<?php

namespace Vatan\Seo\Http\Controllers;

use Illuminate\Http\Request;
use Vatan\Seo\Ai\Ai;
use Vatan\Seo\Ai\ModelRouter;
use Vatan\Seo\Ai\OpenRouterClient;
use Vatan\Seo\Connectors\ConnectorFactory;
use Vatan\Seo\Data\Google\PageSpeed;
use Vatan\Seo\Data\Google\SearchConsole;
use Vatan\Seo\Data\Google\ServiceAccount;
use Vatan\Seo\Data\Rank\DataForSeoRankProvider;
use Vatan\Seo\Models\TelegramAdmin;
use Vatan\Seo\Services\Installer;
use Vatan\Seo\Support\Budget;
use Vatan\Seo\Telegram\SeoBot;

class SettingsController extends BaseController
{
    public function index(ModelRouter $router, OpenRouterClient $client, SeoBot $bot)
    {
        $site = $this->site();
        $roles = [];
        foreach (['fast' => 'کارهای سریع و تکراری', 'strategist' => 'استراتژیست و تحلیل', 'writer' => 'نویسنده‌ی مقاله', 'research' => 'پژوهش زنده‌ی وب'] as $role => $label) {
            $roles[$role] = ['label' => $label, 'model' => $router->resolve($site, $role), 'candidates' => $router->candidates($site, $role)];
        }
        return $this->view('settings', [
            'tiers' => Budget::tiers(),
            'profile' => $site->profile(),
            'roles' => $roles,
            'aiReady' => $client->configured(),
            'keyInfo' => \Illuminate\Support\Facades\Cache::remember('seo-engine:openrouter-key-info', 600, fn () => $client->keyInfo()),
            'dedicated' => filled(config('seo-engine.ai.dedicated_key')),
            'viaGateway' => filled(config('seo-engine.ai.gateway_secret')),
            'liveModels' => count($client->models()),
            'sa' => ['configured' => ServiceAccount::configured(), 'email' => ServiceAccount::email()],
            'bot' => ['configured' => $bot->configured(), 'username' => config('seo-engine.telegram.bot_username'), 'admins' => TelegramAdmin::latest()->get(), 'code' => session('seo_tg_code')],
            'dataforseo' => DataForSeoRankProvider::configured(),
            'connector' => ConnectorFactory::for($site),
            'platforms' => ConnectorFactory::PLATFORMS,
            'connectorConfig' => collect($site->connectorConfig())->except(['app_password'])->all(),
            'indexnow' => (bool) config('seo-engine.indexnow.key'),
        ]);
    }

    public function saveSite(Request $request, Installer $installer)
    {
        $site = $this->site();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'base_url' => 'required|url|max:255',
            'gsc_property' => 'nullable|string|max:255',
            'ga4_property' => 'nullable|string|max:120',
            'monthly_budget_usd' => 'required|numeric|min:0|max:5000',
            'niche' => 'nullable|string|max:255',
            'brand_brief' => 'nullable|string|max:3000',
            'competitors' => 'nullable|string|max:1000',
            'tier_override' => 'nullable|string|max:30',
        ]);
        $site->fill(collect($data)->except(['competitors', 'tier_override'])->all());
        $site->domain = preg_replace('/^www\./', '', (string) parse_url($data['base_url'], PHP_URL_HOST));
        $site->putSetting('competitors', array_values(array_filter(array_map('trim', preg_split('/[\r\n,،]+/u', (string) ($data['competitors'] ?? ''))))));
        $site->putSetting('tier_override', ($data['tier_override'] ?? '') !== '' && isset(Budget::tiers()[$data['tier_override']]) ? $data['tier_override'] : null);
        $site->save();
        $installer->install($site);
        return back()->with('success', 'پروفایل سایت ذخیره شد. پروفایل بودجه‌ی فعال: '.data_get($site->profile(), 'label'));
    }

    public function google(Request $request)
    {
        $request->validate(['service_account' => 'required|file|max:64']);
        try {
            $email = ServiceAccount::store((string) file_get_contents($request->file('service_account')->getRealPath()));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'سرویس‌اکانت ذخیره شد. حالا ایمیل '.$email.' را در سرچ کنسول (Settings ← Users and permissions) با دسترسی Full اضافه کنید.');
    }

    public function connector(Request $request)
    {
        $site = $this->site();
        $data = $request->validate([
            'platform' => 'required|in:'.implode(',', array_keys(ConnectorFactory::PLATFORMS)),
            'url' => 'nullable|url', 'username' => 'nullable|string|max:120', 'app_password' => 'nullable|string|max:200',
            'publish_status' => 'nullable|in:draft,publish', 'category_id' => 'nullable|integer',
        ]);
        $site->platform = $data['platform'];
        $cfg = $site->connectorConfig();
        foreach (['url', 'username', 'publish_status', 'category_id'] as $k) {
            $cfg[$k] = $data[$k] ?? ($cfg[$k] ?? null);
        }
        if (! empty($data['app_password'])) {
            $cfg['app_password'] = $data['app_password'];
        }
        $site->setConnectorConfig(array_filter($cfg, fn ($v) => $v !== null && $v !== ''));
        $site->save();
        [$ok, $msg] = ConnectorFactory::for($site)->health();
        return back()->with($ok ? 'success' : 'warning', $msg);
    }

    public function telegramCode(SeoBot $bot)
    {
        $guard = config('seo-engine.host.admin_guard', 'admin');
        return back()->with('seo_tg_code', $bot->linkCode(auth($guard)->id()))->with('success', 'کد اتصال ساخته شد (۳۰ دقیقه اعتبار).');
    }

    public function telegramSetup(SeoBot $bot)
    {
        $r = $bot->setup();
        $failed = collect($r)->reject(fn ($x) => $x['ok'] ?? false);
        return back()->with($failed->isEmpty() ? 'success' : 'error', $failed->isEmpty() ? 'وب‌هوک و دستورهای بات ثبت شد: '.$bot->webhookUrl() : 'خطا: '.$failed->map(fn ($x, $k) => $k.': '.($x['description'] ?? ''))->implode(' | '));
    }

    public function telegramTest(SeoBot $bot)
    {
        $n = $bot->broadcast('🔔 پیام آزمایشی از موتور سئوی هوشمند — اتصال برقرار است.');
        return back()->with($n ? 'success' : 'warning', $n ? 'پیام آزمایشی برای '.$n.' مدیر ارسال شد.' : 'هیچ مدیر متصلی نیست یا ارسال ناموفق بود.');
    }

    public function test(string $service, Ai $ai, OpenRouterClient $client, SearchConsole $gsc, PageSpeed $psi)
    {
        $site = $this->site();
        @set_time_limit(120);
        try {
            $msg = match ($service) {
                'openrouter' => (function () use ($ai, $client, $site) {
                    \Illuminate\Support\Facades\Cache::forget('seo-engine:openrouter-key-info');
                    $models = count($client->models(true));
                    $reply = $ai->text($site, 'fast', 'connection-test', 'Reply with exactly: OK', 'ping', ['max_tokens' => 5, 'critical' => true]);
                    return 'OpenRouter پاسخ داد ('.trim($reply).'). مدل‌های زنده: '.$models;
                })(),
                'gsc' => 'دسترسی به '.count($gsc->sites()).' property: '.collect($gsc->sites())->pluck('url')->implode('، '),
                'pagespeed' => 'امتیاز موبایل صفحه‌ی اصلی: '.($psi->run($site->url('/'))['scores']['performance'] ?? '—'),
                'connector' => implode(' — ', ConnectorFactory::for($site)->health()).' · محصولات: '.count(ConnectorFactory::for($site)->products(500)),
                default => 'سرویس ناشناخته',
            };
            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'تست ناموفق: '.mb_substr($e->getMessage(), 0, 400));
        }
    }
}
