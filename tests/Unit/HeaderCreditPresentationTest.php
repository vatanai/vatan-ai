<?php

namespace Tests\Unit;

use Tests\TestCase;

class HeaderCreditPresentationTest extends TestCase
{
    public function test_shared_credit_box_keeps_its_value_centered_without_extra_width(): void
    {
        $sharedStyles = file_get_contents(resource_path('views/layouts/partials/nav-styles.blade.php'));
        $publicStyles = file_get_contents(public_path('assets/site/css/home-preview.css'));

        $this->assertMatchesRegularExpression(
            '/\.topnav-token-box\s*\{[^}]*display:\s*grid;[^}]*grid-template-columns:\s*max-content max-content;[^}]*align-items:\s*center;[^}]*justify-content:\s*center;[^}]*column-gap:\s*10\.2px;[^}]*padding:\s*0 8px;/s',
            $sharedStyles
        );
        $this->assertStringContainsString('grid-column: 2;', $sharedStyles);
        $this->assertStringContainsString('top: 1px;', $sharedStyles);
        $this->assertStringContainsString('align-self: center;', $sharedStyles);
        $this->assertStringContainsString('.topnav-token-box.is-guest {', $sharedStyles);
        $this->assertStringContainsString('min-width: 106px;', $sharedStyles);

        $this->assertStringContainsString(
            '.vp-token-box{display:grid;grid-template-columns:max-content max-content;align-items:center;justify-content:center;column-gap:10.2px;',
            $publicStyles
        );
        $this->assertStringContainsString('.vp-token-box.is-guest{min-width:106px;', $publicStyles);
    }

    public function test_guest_gift_label_is_preserved_in_every_shared_header(): void
    {
        $headerFiles = [
            resource_path('views/layouts/nav.blade.php'),
            resource_path('views/app/partials/mobile-header.blade.php'),
            resource_path('views/site/home.blade.php'),
            resource_path('views/site/preview/partials/header.blade.php'),
        ];

        foreach ($headerFiles as $headerFile) {
            $markup = file_get_contents($headerFile);

            $this->assertStringContainsString('is-guest', $markup, $headerFile);
            $this->assertTrue(
                str_contains($markup, 'هدیه') || str_contains($markup, 'headerTokenLabel'),
                $headerFile . ' must render the guest gift label or its shared view variable'
            );
        }

        $provider = file_get_contents(app_path('Providers/AppServiceProvider.php'));
        $this->assertStringContainsString("'headerTokenLabel' => \$isGuest ? 'هدیه' : ''", $provider);
    }
}
