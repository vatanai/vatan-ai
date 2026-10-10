<?php

namespace Vatan\Seo\Support;

/** راهنمای کلیکی: متن‌ها در config/seo-help.php نگهداری می‌شوند (نه در ویوها) تا قابل ترجمه و انتقال باشند. */
class Help
{
    public static function get(string $key): ?array
    {
        $item = config('seo-help.'.$key);
        if (is_string($item)) {
            return ['title' => null, 'body' => $item, 'points' => []];
        }
        return is_array($item) ? $item + ['title' => null, 'body' => null, 'points' => []] : null;
    }
}
