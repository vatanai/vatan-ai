<?php

namespace Tests\Feature;

use App\Support\AppAsset;
use Tests\TestCase;

class AppAssetCacheTest extends TestCase
{
    public function test_shared_assets_have_stable_content_versions(): void
    {
        foreach (['css/fonts.css', 'css/app-footer.css', 'js/profile.js', 'assets/img/icon_vatan.svg'] as $path) {
            $expected = asset($path).'?v='.substr(hash_file('sha256', public_path($path)), 0, 16);
            $this->assertSame($expected, AppAsset::url($path));
            $this->assertSame($expected, AppAsset::url($path));
        }
    }

    public function test_uploads_remote_and_missing_files_keep_their_original_urls(): void
    {
        foreach (['storage/avatar.png', 'https://example.com/icon.svg', 'css/missing-cache-test.css', 'css/../js/profile.js'] as $path) {
            $this->assertSame(asset($path), AppAsset::url($path));
        }
    }

    public function test_changing_file_content_changes_its_url_even_with_the_same_timestamp(): void
    {
        $originalPublicPath = public_path();
        $root = sys_get_temp_dir().'/vatan-asset-'.bin2hex(random_bytes(8));
        mkdir($root.'/css', 0700, true);
        $file = $root.'/css/test.css';
        try {
            app()->usePublicPath($root);
            file_put_contents($file, 'body{color:red}');
            touch($file, 1700000000);
            $first = AppAsset::url('css/test.css');
            file_put_contents($file, 'body{color:tan}');
            touch($file, 1700000000);
            $this->assertNotSame($first, AppAsset::url('css/test.css'));
        } finally {
            app()->usePublicPath($originalPublicPath);
            unlink($file);
            rmdir($root.'/css');
            rmdir($root);
        }
    }

    public function test_every_app_template_compiles_and_its_versioned_assets_exist(): void
    {
        $files = iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views/app'))));
        $files[] = new \SplFileInfo(resource_path('views/layouts/app.blade.php'));
        $files[] = new \SplFileInfo(resource_path('views/layouts/nav.blade.php'));
        $files[] = new \SplFileInfo(resource_path('views/layouts/partials/nav-styles.blade.php'));
        $compiler = app('blade.compiler');
        foreach ($files as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            $compiled = $compiler->compileString($source);
            $this->assertNotEmpty($compiled, $file->getPathname());
            token_get_all($compiled, TOKEN_PARSE);
            preg_match_all("/AppAsset::url\('([^']+)'\)/", $source, $matches);
            foreach ($matches[1] as $path) {
                $this->assertFileExists(public_path($path), $file->getPathname());
                $this->assertMatchesRegularExpression('/\?v=[a-f0-9]{16}$/', AppAsset::url($path));
            }
            $this->assertDoesNotMatchRegularExpression("/\{\{ asset\('(?:css|js)\//", $source, $file->getPathname());
        }
    }
}
