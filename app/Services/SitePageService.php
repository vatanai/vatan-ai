<?php

namespace App\Services;

use App\Models\SitePage;
use Illuminate\Support\Facades\Schema;

class SitePageService
{
    /** @var array<string, SitePage|null> */
    private array $resolved = [];

    public function byKey(string $key): ?SitePage
    {
        if (! Schema::hasTable('site_pages')) {
            return null;
        }

        if (! array_key_exists($key, $this->resolved)) {
            $this->resolved[$key] = SitePage::query()->where('key', $key)->first();
        }

        return $this->resolved[$key];
    }

    public function forRoute(?string $routeName): ?SitePage
    {
        if (! $routeName) {
            return null;
        }

        foreach (config('site_pages.pages', []) as $key => $definition) {
            foreach ($definition['route_names'] ?? [] as $pattern) {
                if ($routeName === $pattern || (str_ends_with($pattern, '.*') && str_starts_with($routeName, rtrim($pattern, '*')))) {
                    return $this->byKey($key);
                }
            }
        }

        return null;
    }

    public function forget(SitePage|string $page): void
    {
        unset($this->resolved[$page instanceof SitePage ? $page->key : $page]);
    }
}
