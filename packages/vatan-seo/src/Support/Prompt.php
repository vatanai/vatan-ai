<?php

namespace Vatan\Seo\Support;

use Vatan\Seo\Models\Site;

/** بارگذاری پرامپت‌های نسخه‌دار از پوشه‌ی prompts و جایگذاری متغیرها */
class Prompt
{
    public static function get(string $name, array $vars = []): string
    {
        $file = dirname(__DIR__, 2).'/prompts/'.$name.'.md';
        $text = is_file($file) ? (string) file_get_contents($file) : '';
        $text = preg_replace('/<!--.*?-->\s*/s', '', $text);
        foreach ($vars as $k => $v) {
            $text = str_replace('{{'.$k.'}}', is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : (string) $v, $text);
        }
        return trim(preg_replace('/\{\{[a-z_]+\}\}/', '', $text));
    }

    public static function brand(Site $site): string
    {
        return trim($site->name.' ('.$site->domain.') — '.($site->brand_brief ?: $site->niche ?: ''));
    }
}
