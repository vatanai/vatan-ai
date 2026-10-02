<?php

namespace App\Services\SmartInstagram\Gateways;

use App\Models\SmartInstagram\Channel;

/**
 * قرارداد اتصال‌دهنده (پروپوزال ۶ — InstagramChannelGateway).
 * Meta مسیر رسمی و هسته است؛ Composio یا n8n فقط پیاده‌سازی‌های قابل‌جایگزینی همین قراردادند.
 */
interface InstagramChannelGateway
{
    public function sendDirectMessage(Channel $channel, string $recipientId, string $text): GatewayResult;

    public function sendPrivateReply(Channel $channel, string $commentId, string $text): GatewayResult;

    /** ارسال کارت محصول به‌عنوان پاسخ خصوصی همان کامنت. */
    public function sendPrivateCard(Channel $channel, string $commentId, array $payload): GatewayResult;

    public function replyToComment(Channel $channel, string $commentId, string $text): GatewayResult;

    public function health(Channel $channel): GatewayResult;

    /** @return GatewayResult data: ['body' => string, 'mime' => ?string] */
    public function downloadMedia(Channel $channel, string $url): GatewayResult;
}
