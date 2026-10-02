<?php

namespace App\Services\SmartInstagram;

use App\Models\SmartInstagram\Conversation;
use App\Models\SmartInstagram\Message;
use App\Models\SmartInstagram\OutboundMessage;
use Illuminate\Support\Carbon;

/**
 * درگاه ارسال قانون‌محور (پروپوزال ۸.۲). پیش از هر ارسال بررسی می‌کند:
 * مالکیت فضای کاری، کلید اصلی و کانال، پنجره‌ی مجاز متا، قفل انسانی، فهرست توقف،
 * سقف دفعات اتومیشن و پاسخ تکراری.
 */
class SendPolicy
{
    /**
     * @return array{allowed:bool,reason:?string,window_expires_at:?Carbon}
     */
    public function evaluate(Conversation $conversation, string $kind, string $origin, string $body, ?string $targetRef = null): array
    {
        $conversation->loadMissing('channel', 'contact');
        $channel = $conversation->channel;
        $contact = $conversation->contact;

        if (!$channel || !$contact || (int) $channel->workspace_id !== (int) $conversation->workspace_id || (int) $contact->workspace_id !== (int) $conversation->workspace_id) {
            return $this->deny('مخاطب یا کانال متعلق به این فضای کاری نیست.');
        }
        if (!config('smart_instagram.outbound_enabled')) {
            return $this->deny('کلید اصلی ارسال در تنظیمات سرور خاموش است (SMART_INSTAGRAM_OUTBOUND_ENABLED).');
        }
        if (!$channel->outbound_enabled) {
            return $this->deny('ارسال برای این کانال خاموش است.');
        }
        if (trim($body) === '') {
            return $this->deny('متن پیام خالی است.');
        }
        if (mb_strlen($body) > 1000) {
            return $this->deny('متن پیام از ۱۰۰۰ نویسه بیشتر است.');
        }

        $automated = in_array($origin, ['ai', 'automation'], true);
        if ($automated && $contact->opted_out) {
            return $this->deny('مخاطب در فهرست توقف است.');
        }
        if ($automated && ($conversation->ai_paused || $conversation->needs_human)) {
            return $this->deny('گفتگو به انسان واگذار شده است؛ ارسال خودکار متوقف است.');
        }

        $window = null;
        if ($kind === 'dm') {
            if (!$conversation->last_inbound_at) {
                return $this->deny('مشتری هنوز پیامی نفرستاده؛ شروع گفتگو (دایرکت سرد) مجاز نیست.');
            }
            $window = $conversation->last_inbound_at->copy()->addHours((int) config('smart_instagram.policy.dm_window_hours', 24));
            if ($window->isPast()) {
                return $this->deny('پنجره‌ی ۲۴ساعته‌ی پیام‌رسانی متا بسته شده است.');
            }
        }

        if (in_array($kind, ['private_reply', 'public_reply'], true)) {
            if (!$targetRef) {
                return $this->deny('شناسه‌ی کامنت برای پاسخ مشخص نیست.');
            }
            $comment = Message::query()
                ->where('conversation_id', $conversation->id)
                ->where('meta->comment_id', $targetRef)
                ->first();
            if (!$comment) {
                return $this->deny('کامنت مرجع در این گفتگو پیدا نشد.');
            }
            if ($kind === 'private_reply') {
                $window = $comment->occurred_at->copy()->addDays((int) config('smart_instagram.policy.private_reply_window_days', 7));
                if ($window->isPast()) {
                    return $this->deny('مهلت پاسخ خصوصی به این کامنت تمام شده است.');
                }
            }
            $already = OutboundMessage::query()
                ->where('conversation_id', $conversation->id)
                ->where('kind', $kind)
                ->where('target_ref', $targetRef)
                ->whereIn('status', ['pending', 'sending', 'sent'])
                ->exists();
            if ($already) {
                return $this->deny($kind === 'private_reply' ? 'برای هر کامنت فقط یک پاسخ خصوصی مجاز است.' : 'به این کامنت قبلاً پاسخ عمومی داده شده است.');
            }
        }

        $duplicate = OutboundMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('body', $body)
            ->whereIn('status', ['pending', 'sending', 'sent'])
            ->where('created_at', '>=', now()->subMinutes((int) config('smart_instagram.policy.duplicate_window_minutes', 30)))
            ->exists();
        if ($duplicate) {
            return $this->deny('همین متن به‌تازگی برای این مشتری ارسال شده است.');
        }

        if ($origin === 'automation') {
            $todayCount = OutboundMessage::query()
                ->where('contact_id', $contact->id)
                ->where('origin', 'automation')
                ->whereIn('status', ['pending', 'sending', 'sent'])
                ->where('created_at', '>=', now()->subDay())
                ->count();
            if ($todayCount >= (int) config('smart_instagram.policy.automation_max_per_contact_per_day', 3)) {
                return $this->deny('سقف پیام خودکار روزانه برای این مشتری پر شده است.');
            }
        }

        return ['allowed' => true, 'reason' => null, 'window_expires_at' => $window];
    }

    private function deny(string $reason): array
    {
        return ['allowed' => false, 'reason' => $reason, 'window_expires_at' => null];
    }
}
