<?php

namespace App\Services\SmartInstagram\Gateways;

use App\Models\SmartInstagram\Channel;
use Illuminate\Support\Str;

/**
 * کانال آزمایشی: هیچ درخواست بیرونی نمی‌فرستد و فقط موفقیت شبیه‌سازی‌شده برمی‌گرداند.
 * برای تست سناریوها روی «حساب آزمایشی» پیش از حساب واقعی (پروپوزال ۱۳ — فاز دو).
 */
class SandboxInstagramGateway implements InstagramChannelGateway
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
}
