<?php

namespace Vatan\Content\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** فهرست «محصول وطن» قابل انتخاب برای هر پست: محصولات فعال سایت + شات‌های استودیو محصول */
class Catalog
{
    public static function products(): array
    {
        return Cache::remember('cf:catalog', 300, function () {
            $out = [];
            try {
                if (Schema::hasTable('products')) {
                    $pattern = (string) config('seo-engine.host.product_url', '/product/{slug}');
                    foreach (DB::table('products')->where('status', 'active')->orderByDesc('is_featured')->orderBy('name_fa')->limit(400)->get(['id', 'name_fa', 'slug', 'category']) as $p) {
                        $out[] = ['value' => 'product:'.$p->id, 'label' => $p->name_fa, 'group' => 'محصولات سایت', 'url' => url(str_replace('{slug}', $p->slug, $pattern))];
                    }
                }
                if (Schema::hasTable('shot_library')) {
                    foreach (DB::table('shot_library')->where('is_active', true)->orderBy('sort')->get(['key', 'name_fa']) as $s) {
                        $out[] = ['value' => 'shot:'.$s->key, 'label' => 'استودیو محصول · '.$s->name_fa, 'group' => 'شات‌های استودیو محصول', 'url' => url('/')];
                    }
                }
            } catch (\Throwable) {
            }
            $out[] = ['value' => 'brand:vatan', 'label' => 'برند وطن (معرفی کلی خدمات)', 'group' => 'عمومی', 'url' => url('/')];

            return $out;
        });
    }
}
