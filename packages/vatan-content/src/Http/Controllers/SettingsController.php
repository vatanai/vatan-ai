<?php

namespace Vatan\Content\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Vatan\Content\Models\Setting;
use Vatan\Content\Services\Ai;
use Vatan\Content\Services\LabSync;
use Vatan\Content\Services\Telegram;

class SettingsController extends Controller
{
    public function index(Telegram $tg, Ai $ai)
    {
        $lab = config('content-factory.lab_path');
        $tc = $tg->config();
        $agent = (array) Setting::get('agent', [], $this->ws());

        return view('cf::settings', [
            'cfTitle' => 'تنظیمات و اتصال‌ها', 'cfIcon' => 'fa-sliders', 'cfHelp' => 'settings',
            'cfSubtitle' => 'ایجنت مک، تلگرام، مدل‌ها و همگام‌سازی.',
            'checks' => [
                ['ایجنت مک', JobController::agentOnline(), JobController::agentOnline() ? 'آنلاین · '.($agent['host'] ?? '') : 'خاموش — دستور اجرا پایین همین صفحه است'],
                ['هوش مصنوعی (OpenRouter)', $ai->available(), 'کپشن، تشخیص سبک و تحلیل. از ایران فقط از سرور با پل اتصال کار می‌کند'],
                ['کلید fal (کلیپ هوش مصنوعی)', filled(config('services.fal.api_key')), 'برای صحنه‌های کلیپ AI'],
                ['بات تلگرام', filled($tc['token']), $tc['token'] ? 'تنظیم شده' : 'توکن را پایین وارد کنید'],
                ['گروه تلگرام', filled($tc['chat_id']), $tc['chat_id'] ? 'شناسه: '.$tc['chat_id'] : 'شناسه‌ی گروه'],
                ['تاپیک‌های گروه', count(array_filter($tc['topics'])) > 0, count(array_filter($tc['topics'])).' تاپیک'],
                ['اینستاگرام هوشمند', class_exists(\App\Models\SmartInstagram\Post::class), 'اتصال پست، سناریوی دایرکت و آمار'],
                ['پوشه‌ی content-lab روی همین سرور', is_dir($lab), is_dir($lab) ? 'برای همگام‌سازی دستی' : 'روی سرور اصلی لازم نیست؛ ایجنت مک کار می‌کند'],
            ],
            'lastSync' => Setting::get('last_sync', null, $this->ws()),
            'models' => config('content-factory.ai.roles'),
            'agentToken' => AgentController::token(), 'agent' => $agent, 'tg' => $tc,
            'serverUrl' => url('/'),
            'isLocal' => is_file(rtrim((string) $lab, '/').'/_system/agent/agent.py'),
            'remote' => (array) Setting::get('remote_server', [], $this->ws()),
        ]);
    }

    public function sync()
    {
        if (! is_dir(config('content-factory.lab_path'))) {
            return back()->with('warning', 'پوشه‌ی content-lab روی این سرور نیست. از دکمه‌های «تحلیل» استفاده کنید تا ایجنت مک انجام دهد.');
        }
        $log = (new LabSync())->run();
        Setting::put('last_sync', ['at' => now()->toIso8601String(), 'log' => $log], $this->ws());

        return back()->with('success', 'همگام‌سازی انجام شد: '.implode(' · ', $log));
    }

    /** نصب ایجنت دائمی (LaunchAgent) روی همین مک — فقط وقتی داشبورد روی مک اجرا می‌شود. */
    public function agentInstall(Request $request)
    {
        $d = $request->validate(['online_url' => 'nullable|url|max:200', 'online_token' => 'nullable|string|max:120']);
        $lab = rtrim((string) config('content-factory.lab_path'), '/');
        $agent = $lab.'/_system/agent/agent.py';
        if (! is_file($agent) || ! function_exists('exec')) {
            return back()->with('warning', 'این دکمه فقط روی داشبورد لوکالِ مک کار می‌کند (پوشه‌ی content-lab اینجا نیست).');
        }
        Setting::put('remote_server', ['url' => $d['online_url'] ?? null, 'token' => $d['online_token'] ?? null], $this->ws());
        $python = (string) config('content-factory.runner.python', 'python3');
        $path = implode(':', (array) config('content-factory.runner.extra_paths', [])).':'.getenv('PATH');
        $args = ' --install --server '.escapeshellarg(rtrim((string) config('content-factory.runner.local_url', config('app.url')), '/')).' --token '.escapeshellarg(AgentController::token());
        if (! empty($d['online_url']) && ! empty($d['online_token'])) {
            $args .= ' --server '.escapeshellarg(rtrim($d['online_url'], '/')).' --token '.escapeshellarg($d['online_token']);
        }
        $out = [];
        @exec('PATH='.escapeshellarg($path).' '.escapeshellcmd($python).' '.escapeshellarg($agent).$args.' 2>&1', $out, $code);
        $res = json_decode((string) end($out), true);
        if ($code === 0 && ($res['ok'] ?? false)) {
            return back()->with('success', 'ایجنت دائمی نصب و روشن شد؛ از این به بعد با روشن شدن مک خودکار اجرا می‌شود.');
        }

        return back()->with('error', 'نصب ایجنت ناموفق: '.mb_substr(implode(' ', $out), -400));
    }

    public function agentToken()
    {
        Setting::put('agent_token', Str::random(40), $this->ws());

        return back()->with('success', 'توکن ایجنت عوض شد؛ دستور جدید را روی مک اجرا کنید.');
    }

    public function telegram(Request $request, Telegram $tg)
    {
        $d = $request->validate(['token' => 'nullable|string|max:120', 'chat_id' => 'nullable|string|max:40', 'action' => 'required|in:save,topics,webhook,test,detect']);
        $patch = array_filter(['token' => $d['token'] ?? null, 'chat_id' => $d['chat_id'] ?? null]);
        if ($patch) {
            $tg->save($patch);
        }
        try {
            switch ($d['action']) {
                case 'detect':
                    $tg->call('deleteWebhook');
                    $chats = collect($tg->call('getUpdates', ['limit' => 50]))->map(fn ($u) => ($u['message'] ?? $u['my_chat_member'] ?? [])['chat'] ?? null)
                        ->filter(fn ($c) => $c && in_array($c['type'] ?? '', ['supergroup', 'group'], true))->unique('id')->values();
                    if ($chats->isEmpty()) {
                        return back()->with('warning', 'گروهی پیدا نشد. یک پیام در گروه بفرستید و دوباره بزنید.');
                    }
                    $tg->save(['chat_id' => (string) $chats->first()['id']]);

                    return back()->with('success', 'گروه «'.($chats->first()['title'] ?? '').'» پیدا و ذخیره شد.');
                case 'topics':
                    $t = $tg->setupTopics();

                    return back()->with('success', count(array_filter($t)).' تاپیک آماده است.');
                case 'webhook':
                    $secret = $tg->config()['webhook_secret'] ?: Str::random(32);
                    $tg->save(['webhook_secret' => $secret]);
                    $tg->call('setWebhook', ['url' => route('cf.api.telegram', $secret), 'allowed_updates' => ['callback_query', 'message']]);

                    return back()->with('success', 'وب‌هوک دکمه‌های تأیید روی همین سرور تنظیم شد.');
                case 'test':
                    $tg->send('review', '✅ اتصال کارخانه‌ی محتوای وطن برقرار است.');

                    return back()->with('success', 'پیام آزمایشی ارسال شد.');
            }
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تنظیمات تلگرام ذخیره شد.');
    }
}
