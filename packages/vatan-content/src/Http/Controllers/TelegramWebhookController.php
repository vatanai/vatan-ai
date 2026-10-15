<?php

namespace Vatan\Content\Http\Controllers;

use Illuminate\Http\Request;
use Vatan\Content\Models\Production;
use Vatan\Content\Services\Telegram;

/** دکمه‌های تأیید/اصلاح پیش‌نمایش در گروه تلگرام */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $r, string $secret, Telegram $tg)
    {
        abort_unless(hash_equals((string) ($tg->config()['webhook_secret'] ?? ''), $secret), 404);
        $cb = $r->input('callback_query');
        if (! $cb) {
            return ['ok' => true];
        }
        [$ns, $action, $id] = array_pad(explode(':', (string) ($cb['data'] ?? '')), 3, null);
        $p = $ns === 'cf' ? Production::find((int) $id) : null;
        $who = trim(($cb['from']['first_name'] ?? '').' '.($cb['from']['last_name'] ?? '')) ?: 'تلگرام';
        $msg = 'نامعتبر';
        if ($p && $action === 'approve' && $p->current_version > 0) {
            $p->update(['status' => 'approved', 'review_note' => 'تأیید در تلگرام توسط '.$who]);
            $p->versions()->where('version', $p->current_version)->update(['status' => 'approved']);
            $p->item?->update(['production_status' => 'approved']);
            $msg = '✅ تأیید شد';
            $tg->send('final', '✅ <b>'.e($p->title).'</b> تأیید شد (نسخه‌ی '.$p->current_version.") — آماده‌ی انتشار\n".route('cf.productions.show', $p));
        } elseif ($p && $action === 'revise') {
            $p->update(['status' => 'revision']);
            $msg = 'برای ثبت بازه‌ی اصلاحیه، داشبورد را باز کنید';
        }
        try {
            $tg->call('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => $msg]);
        } catch (\Throwable) {
        }

        return ['ok' => true];
    }
}
