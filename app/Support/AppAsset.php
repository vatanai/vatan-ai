<?php

namespace App\Support;

final class AppAsset
{
    /** Version public UI files by content; never version uploads or remote URLs. */
    public static function url(string $path): string
    {
        if (! preg_match('#\A(?:css/|js/|fonts/|assets/(?:img|icons)/)[a-zA-Z0-9_./-]+\.(?:css|js|woff2?|ttf|otf|svg|png|webp|avif|jpe?g|ico)\z#', $path)
            || str_contains($path, '..')) {
            return asset($path);
        }

        $file = public_path($path);
        if (! is_file($file) || ! is_readable($file)) {
            return asset($path);
        }

        $hash = hash_file('sha256', $file);

        return asset($path).($hash === false ? '' : '?v='.substr($hash, 0, 16));
    }
}
