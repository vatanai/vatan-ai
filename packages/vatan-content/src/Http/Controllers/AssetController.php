<?php

namespace Vatan\Content\Http\Controllers;

class AssetController extends Controller
{
    public function __invoke(string $file)
    {
        $path = dirname(__DIR__, 3).'/resources/assets/'.basename($file);
        abort_unless(is_file($path), 404);
        $type = str_ends_with($file, '.css') ? 'text/css' : 'application/javascript';

        return response()->file($path, ['Content-Type' => $type.'; charset=utf-8', 'Cache-Control' => 'public, max-age=86400']);
    }
}
