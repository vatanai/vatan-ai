<?php

namespace Vatan\Seo\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Vatan\Seo\Models\Site;

class PublicFilesController extends Controller
{
    public function llms()
    {
        $text = Schema::hasTable('seo_sites') ? Site::where('is_active', true)->orderBy('id')->first()?->setting('llms_txt') : null;
        abort_if(blank($text), 404);
        return response($text, 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }
}
