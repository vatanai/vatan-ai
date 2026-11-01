<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;
use Vatan\Seo\Data\Google\PageSpeed;
use Vatan\Seo\Data\Google\SearchConsole;

class GoogleQuotaProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'seo-engine.google.gateway_url' => null, 'seo-engine.google.gateway_secret' => null]);
        Cache::flush();
    }

    public function test_sitemap_submission_sends_the_required_empty_body(): void
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        self::assertNotFalse($key);
        self::assertTrue(openssl_pkey_export($key, $privateKey));
        $path = tempnam(sys_get_temp_dir(), 'seo-service-account-');
        self::assertNotFalse($path);
        file_put_contents($path, json_encode([
            'type' => 'service_account',
            'client_email' => 'seo-test@example.test',
            'private_key' => $privateKey,
        ], JSON_THROW_ON_ERROR));
        config(['seo-engine.google.service_account_path' => $path]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'unit-test-token', 'expires_in' => 3600]),
            'https://www.googleapis.com/webmasters/v3/sites/*' => Http::response('', 204),
        ]);

        try {
            app(SearchConsole::class)->submitSitemap('sc-domain:aivatan.com', 'https://aivatan.com/sitemap.xml');
            Http::assertSent(fn (Request $request) => $request->method() === 'PUT'
                && $request->body() === ''
                && str_contains($request->url(), '/sitemaps/https%3A%2F%2Faivatan.com%2Fsitemap.xml'));
        } finally {
            @unlink($path);
            Cache::flush();
        }
    }

    public function test_successful_pagespeed_results_are_cached_for_a_week(): void
    {
        Http::fake(['https://www.googleapis.com/pagespeedonline/v5/runPagespeed*' => Http::response([
            'lighthouseResult' => [
                'categories' => [
                    'performance' => ['score' => 0.9],
                    'seo' => ['score' => 1],
                    'best-practices' => ['score' => 1],
                    'accessibility' => ['score' => 1],
                ],
                'audits' => [],
            ],
        ])]);

        $service = app(PageSpeed::class);
        $first = $service->run('https://aivatan.com/', 'mobile');
        $second = $service->run('https://aivatan.com/', 'mobile');

        self::assertSame($first, $second);
        Http::assertSentCount(1);
    }

    public function test_pagespeed_errors_are_cooled_down_instead_of_repeating_quota_calls(): void
    {
        Http::fake(['https://www.googleapis.com/pagespeedonline/v5/runPagespeed*' => Http::response([
            'error' => ['message' => 'Quota exceeded'],
        ], 429)]);

        $service = app(PageSpeed::class);
        foreach ([1, 2] as $_) {
            try {
                $service->run('https://aivatan.com/', 'desktop');
                self::fail('Expected a quota error.');
            } catch (RuntimeException $e) {
                self::assertStringContainsString('Quota exceeded', $e->getMessage());
            }
        }

        Http::assertSentCount(1);
    }
}
