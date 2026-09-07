<?php

namespace Tests\Unit;

use Tests\TestCase;

class GoogleSiteVerificationTest extends TestCase
{
    public function test_google_verification_meta_is_included_in_public_home_and_shared_site_layout(): void
    {
        $verification = file_get_contents(resource_path('views/partials/google-site-verification.blade.php'));
        $publicHome = file_get_contents(resource_path('views/site/preview/home.blade.php'));
        $sharedLayout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString(
            '<meta name="google-site-verification" content="hKfYF3DzJstYdFHHfD_hb9AFibk_ggXOFRmqKM99pmg">',
            $verification
        );
        $this->assertStringContainsString("@include('partials.google-site-verification')", $publicHome);
        $this->assertStringContainsString("@include('partials.google-site-verification')", $sharedLayout);
    }
}
