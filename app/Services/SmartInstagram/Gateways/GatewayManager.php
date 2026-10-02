<?php

namespace App\Services\SmartInstagram\Gateways;

use App\Models\SmartInstagram\Channel;
use InvalidArgumentException;

class GatewayManager
{
    public function for(Channel $channel): InstagramChannelGateway
    {
        $class = config('smart_instagram.gateways.'.$channel->gateway);
        if (!$class || !is_subclass_of($class, InstagramChannelGateway::class)) {
            throw new InvalidArgumentException('اتصال‌دهنده‌ی «'.$channel->gateway.'» تعریف نشده است.');
        }

        return app($class);
    }

    /** @return array<string,string> */
    public function available(): array
    {
        return [
            'meta' => 'Meta (رسمی)',
            'composio' => 'Composio (Instagram API)',
            'sandbox' => 'آزمایشی (بدون ارسال واقعی)',
        ];
    }
}
