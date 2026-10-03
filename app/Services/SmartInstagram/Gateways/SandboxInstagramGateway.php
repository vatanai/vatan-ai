<?php

namespace App\Services\SmartInstagram\Gateways;

use App\Models\SmartInstagram\Channel;
use Illuminate\Support\Str;

/**
 * کانال آزمایشی: هیچ درخواست بیرونی نمی‌فرستد و فقط موفقیت شبیه‌سازی‌شده برمی‌گرداند.
 * برای تست سناریوها روی «حساب آزمایشی» پیش از حساب واقعی (پروپوزال ۱۳ — فاز دو).
 */
class SandboxInstagramGateway implements InstagramChannelGateway, RichInstagramGateway
{
    public function sendDirectMessage(Channel $channel, string $recipientId, string $text): GatewayResult
    {
        return GatewayResult::success('ارسال آزمایشی ثبت شد (بدون تماس بیرونی).', 'sandbox_'.Str::lower(Str::random(16)));
    }

    public function sendPrivateReply(Channel $channel, string $commentId, string $text): GatewayResult
    {
        return $this->sendDirectMessage($channel, $commentId, $text);
    }

    public function sendPrivateCard(Channel $channel, string $commentId, array $payload): GatewayResult
    {
        return $this->sendDirectMessage($channel, $commentId, (string) ($payload['fallback_text'] ?? 'کارت محصول'));
    }

    public function replyToComment(Channel $channel, string $commentId, string $text): GatewayResult
    {
        return $this->sendDirectMessage($channel, $commentId, $text);
    }

    public function health(Channel $channel): GatewayResult
    {
        return GatewayResult::success('کانال آزمایشی همیشه سالم است.', null, ['profile' => ['username' => $channel->username ?: 'sandbox']]);
    }

    public function downloadMedia(Channel $channel, string $url): GatewayResult
    {
        return GatewayResult::failure('کانال آزمایشی رسانه‌ی واقعی ندارد.');
    }

    // ───── RichInstagramGateway — همه چیز شبیه‌سازی‌شده و بدون تماس بیرونی ─────

    public function sendRichMessage(Channel $channel, array $recipient, array $message, ?string $fallbackText = null): GatewayResult
    {
        return GatewayResult::success('پیام ساختاریافته‌ی آزمایشی ثبت شد (بدون تماس بیرونی).', 'sandbox_'.Str::lower(Str::random(16)));
    }

    /** در کانال آزمایشی با تنظیم sandbox_follow (true/false/unknown) قابل شبیه‌سازی است. */
    public function followStatus(Channel $channel, string $igsid): ?bool
    {
        $value = data_get((array) $channel->settings, 'sandbox_follow', true);

        return is_bool($value) ? $value : null;
    }

    public function listMedia(Channel $channel, int $limit = 25): GatewayResult
    {
        return GatewayResult::success('کانال آزمایشی رسانه‌ی واقعی ندارد.', null, ['items' => []]);
    }

    public function getMedia(Channel $channel, string $mediaId): GatewayResult
    {
        return GatewayResult::failure('کانال آزمایشی رسانه‌ی واقعی ندارد.');
    }

    public function mediaInsights(Channel $channel, string $mediaId): GatewayResult
    {
        return GatewayResult::failure('کانال آزمایشی آمار واقعی ندارد.');
    }
}
