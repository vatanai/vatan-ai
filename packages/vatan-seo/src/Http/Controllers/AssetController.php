<?php

namespace Vatan\Seo\Http\Controllers;

use Illuminate\Routing\Controller;

/** سرو فایل‌های CSS/JS پکیج از داخل خودش (بدون نیاز به publish) با کش طولانی */
class AssetController extends Controller
{
    public function __invoke(string $file)
    {
        $path = dirname(__DIR__, 3).'/resources/assets/'.basename($file);
        abort_unless(is_file($path), 404);
        $type = str_ends_with($file, '.css') ? 'text/css' : 'application/javascript';
        return response()->file($path, ['Content-Type' => $type.'; charset=UTF-8', 'Cache-Control' => 'public, max-age=31536000, immutable']);
    }
}
