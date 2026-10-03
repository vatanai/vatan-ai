<?php

namespace App\Services\SmartInstagram\Gateways;

use App\Models\SmartInstagram\Channel;

/**
 * قابلیت‌های تکمیلی «ثبت پست» — جدا از قرارداد اصلی تا اتصال‌دهنده‌های فعلی بدون تغییر کار کنند.
 * کد دامنه پیش از استفاده instanceof را بررسی می‌کند.
 */
interface RichInstagramGateway
{
    /**
     * ارسال پیام ساختاریافته (متن + quick_replies یا قالب کارت).
     * @param array{id?:string,comment_id?:string} $recipient
     */
    public function sendRichMessage(Channel $channel, array $recipient, array $message, ?string $fallbackText = null): GatewayResult;

    /** وضعیت فالو مخاطب؛ null یعنی API پاسخ قطعی نداد. */
    public function followStatus(Channel $channel, string $igsid): ?bool;

    /** فهرست رسانه‌های حساب. data: ['items' => array<int,array>] */
    public function listMedia(Channel $channel, int $limit = 25): GatewayResult;

    /** یک رسانه با شناسه. data: ['item' => array] */
    public function getMedia(Channel $channel, string $mediaId): GatewayResult;

    /** آمار تکمیلی (ذخیره، اشتراک، دسترسی). data: ['saved'=>?int,'shares'=>?int,'reach'=>?int] */
    public function mediaInsights(Channel $channel, string $mediaId): GatewayResult;
}
