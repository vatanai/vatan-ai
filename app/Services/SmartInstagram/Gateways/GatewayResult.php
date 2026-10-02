<?php

namespace App\Services\SmartInstagram\Gateways;

/** نتیجه‌ی استاندارد هر عملیات اتصال‌دهنده؛ کد دامنه فقط همین را می‌بیند. */
final class GatewayResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly ?string $externalId = null,
        public readonly bool $retryable = false,
        public readonly array $data = [],
    ) {
    }

    public static function success(string $message, ?string $externalId = null, array $data = []): self
    {
        return new self(true, $message, $externalId, false, $data);
    }

    public static function failure(string $message, bool $retryable = false, array $data = []): self
    {
        return new self(false, $message, null, $retryable, $data);
    }
}
