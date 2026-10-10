<?php

namespace Vatan\Seo\Connectors;

/** تبدیل بلوک‌های محتوای موتور به HTML تمیز (برای وردپرس و پیش‌نمایش) */
class BlocksToHtml
{
    public static function render(array $blocks): string
    {
        $html = '';
        foreach ($blocks as $b) {
            $e = fn ($k) => e((string) ($b[$k] ?? ''));
            $html .= match ($b['type'] ?? '') {
                'lead' => '<p><strong>'.$e('content').'</strong></p>',
                'heading' => sprintf('<h%1$d>%2$s</h%1$d>', in_array((int) ($b['level'] ?? 2), [2, 3, 4], true) ? (int) $b['level'] : 2, $e('content')),
                'paragraph' => '<p>'.$e('content').'</p>',
                'list' => '<ul>'.collect((array) ($b['items'] ?? []))->map(fn ($i) => '<li>'.e((string) $i).'</li>')->implode('').'</ul>',
                'note' => '<blockquote>'.$e('content').'</blockquote>',
                'quote' => '<blockquote>'.$e('content').'</blockquote>',
                'cta' => '<p><a href="'.$e('button_url').'">'.($e('button_label') ?: $e('title')).'</a></p>',
                default => '',
            };
        }
        return self::links($html);
    }

    /** لینک‌های Markdown داخل متن ([انکر](url)) ← تگ a (فقط http(s) یا مسیر نسبی) */
    public static function links(string $html): string
    {
        return (string) preg_replace('/\[([^\]]+)\]\(((?:https?:\/\/|\/)[^)\s]+)\)/u', '<a href="$2">$1</a>', $html);
    }
}
