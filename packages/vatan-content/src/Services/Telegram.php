<?php

namespace Vatan\Content\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Vatan\Content\Models\Production;
use Vatan\Content\Models\Setting;
use Vatan\Content\Models\TelegramLink;

/**
 * گروه موضوعی تلگرام کارخانه: ساخت تاپیک‌ها، ارسال پیش‌نمایش برای تأیید، دکمه‌های تأیید/رد.
 * منبع اصلی داده همیشه داشبورد است؛ هر پیام به رکورد تولید وصل می‌شود.
 */
class Telegram
{
    private function ws(): int
    {
        return (int) config('content-factory.workspace_id', 1);
    }

    public function config(): array
    {
        $s = (array) Setting::get('telegram', [], $this->ws());

        return [
            'token' => $s['token'] ?? config('content-factory.telegram.bot_token'),
            'chat_id' => $s['chat_id'] ?? config('content-factory.telegram.chat_id'),
            'topics' => (array) ($s['topics'] ?? []),
            'webhook_secret' => $s['webhook_secret'] ?? null,
        ];
    }

    public function save(array $patch): void
    {
        Setting::put('telegram', array_merge((array) Setting::get('telegram', [], $this->ws()), $patch), $this->ws());
    }

    public function ready(): bool
    {
        $c = $this->config();

        return filled($c['token']) && filled($c['chat_id']);
    }

    public function call(string $method, array $params = [], array $files = []): array
    {
        $token = $this->config()['token'];
        throw_if(! $token, new \RuntimeException('توکن بات تلگرام تنظیم نشده است.'));
        $req = Http::timeout(60);
        foreach ($files as $name => [$contents, $filename]) {
            $req = $req->attach($name, $contents, $filename);
        }
        $url = rtrim((string) config('content-factory.telegram.api_base', 'https://api.telegram.org'), '/').'/bot'.$token.'/'.$method;
        $r = $files ? $req->post($url, $params) : $req->asJson()->post($url, $params);
        $j = (array) $r->json();
        throw_unless($j['ok'] ?? false, new \RuntimeException('تلگرام: '.($j['description'] ?? 'HTTP '.$r->status())));

        return (array) ($j['result'] ?? []);
    }

    /** ساخت تاپیک‌های گروه (اگر Topics فعال و بات مدیر با دسترسی Manage Topics باشد) */
    public function setupTopics(): array
    {
        $c = $this->config();
        $topics = $c['topics'];
        foreach (config('content-factory.telegram.topics') as $key => $name) {
            if (! empty($topics[$key])) {
                continue;
            }
            $r = $this->call('createForumTopic', ['chat_id' => $c['chat_id'], 'name' => $name]);
            $topics[$key] = $r['message_thread_id'] ?? null;
        }
        $this->save(['topics' => $topics]);

        return $topics;
    }

    public function send(string $topic, string $text, array $extra = []): ?array
    {
        if (! $this->ready()) {
            return null;
        }
        $c = $this->config();

        return $this->call('sendMessage', array_filter(['chat_id' => $c['chat_id'], 'message_thread_id' => $c['topics'][$topic] ?? null, 'text' => $text, 'parse_mode' => 'HTML'] + $extra));
    }

    /** پیش‌نمایش نسخه‌ی جدید با دکمه‌های تأیید/رد در تاپیک «در انتظار تأیید» */
    public function notifyReview(Production $p): void
    {
        if (! $this->ready()) {
            return;
        }
        $v = $p->versions()->first();
        $c = $this->config();
        $disk = Storage::disk(config('content-factory.disk', 'public'));
        $caption = "🎬 <b>".e($p->title)."</b>\n".e($p->item?->code ?? $p->code).' · نسخه‌ی '.$p->current_version.' · '.e($p->format ?? '')
            ."\n\n".e(mb_substr((string) $p->caption, 0, 700));
        $kb = ['inline_keyboard' => [[['text' => '✅ تأیید', 'callback_data' => 'cf:approve:'.$p->id], ['text' => '✏️ نیاز به اصلاح', 'callback_data' => 'cf:revise:'.$p->id]],
            [['text' => 'باز کردن در داشبورد', 'url' => route('cf.productions.show', $p)]]]];
        $base = ['chat_id' => $c['chat_id'], 'message_thread_id' => $c['topics']['review'] ?? null, 'caption' => $caption, 'parse_mode' => 'HTML', 'reply_markup' => json_encode($kb)];
        try {
            if ($v?->video_path && $disk->exists($v->video_path)) {
                $r = $this->call('sendVideo', array_filter($base) + ['supports_streaming' => 'true'], ['video' => [$disk->get($v->video_path), basename($v->video_path)]]);
            } elseif ($p->cover_path && $disk->exists($p->cover_path)) {
                $r = $this->call('sendPhoto', array_filter($base), ['photo' => [$disk->get($p->cover_path), basename($p->cover_path)]]);
            } else {
                $r = $this->send('review', $caption, ['reply_markup' => $kb]);
            }
            TelegramLink::create(['workspace_id' => $p->workspace_id, 'chat_id' => (string) $c['chat_id'], 'thread_id' => (string) ($c['topics']['review'] ?? ''),
                'message_id' => (string) ($r['message_id'] ?? ''), 'topic' => 'review', 'linkable_type' => Production::class, 'linkable_id' => $p->id,
                'payload' => ['version' => $p->current_version]]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ContentFactory telegram', ['e' => $e->getMessage()]);
        }
    }
}
