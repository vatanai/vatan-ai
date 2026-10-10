<?php

namespace Vatan\Seo\Checks;

/** نتیجه‌ی یک بررسی: pass=true قبول | false رد | null نامشخص (هنوز داده نیست) */
final class CheckResult
{
    public function __construct(public ?bool $pass, public string $message, public array $data = []) {}

    public static function ok(string $m, array $d = []): self { return new self(true, $m, $d); }
    public static function fail(string $m, array $d = []): self { return new self(false, $m, $d); }
    public static function wait(string $m, array $d = []): self { return new self(null, $m, $d); }
}
