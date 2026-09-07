<?php

namespace Tests\Unit;

use Tests\TestCase;

class RequestedUiPresentationTest extends TestCase
{
    public function test_trend_cards_show_product_token_cost_instead_of_download_count(): void
    {
        $card = file_get_contents(resource_path('views/app/trends/partials/product-card.blade.php'));
        $service = file_get_contents(app_path('Services/Explore/TrendsService.php'));

        $this->assertStringContainsString("['tokens']", $card);
        $this->assertStringContainsString('توکن', $card);
        $this->assertStringContainsString("'tokens' => \$product->qualityCreditCost('standard')", $service);
        $this->assertStringNotContainsString("['downloads']", $card);
    }

    public function test_profile_placeholder_is_a_visible_brand_icon_for_users_without_avatar(): void
    {
        $header = file_get_contents(resource_path('views/app/profile/header.blade.php'));
        $styles = file_get_contents(public_path('css/profile.css'));

        $this->assertStringContainsString('assets/img/icons/nav-profile.svg', $header);
        $this->assertMatchesRegularExpression('/\.avatar-img--placeholder\s*\{[^}]*background:\s*var\(--bg-card\);[^}]*opacity:\s*1;/s', $styles);
    }

    public function test_users_table_has_scoped_compact_output_boxes_and_wider_status_controls(): void
    {
        $view = file_get_contents(resource_path('views/admin/users/index.blade.php'));
        $styles = file_get_contents(public_path('admin/css/user-operational-snapshot.css'));

        $this->assertStringContainsString('users-index-table', $view);
        $this->assertStringContainsString('admin-credit-report', $view);
        $this->assertStringContainsString('user-status-control', $view);
        $this->assertStringContainsString('min-width: 174px;', $styles);
        $this->assertStringContainsString('max-w-[120px]', $view);
        $this->assertStringContainsString('padding-inline: 4px !important;', $styles);
    }

    public function test_home_tablet_search_controls_share_one_height_and_compact_type(): void
    {
        $view = file_get_contents(resource_path('views/app/home.blade.php'));

        $this->assertStringContainsString('@media (min-width: 640px) and (max-width: 1023px)', $view);
        $this->assertStringContainsString('.ig-generate--desktop,', $view);
        $this->assertStringContainsString('height: 44px; min-height: 44px;', $view);
        $this->assertStringContainsString('.ig-quick-title { font-size: 10px;', $view);
        $this->assertStringContainsString('.ig-quick-sub { font-size: 8px;', $view);
    }

    public function test_admin_user_token_management_supports_expiry_bulk_popup_and_optional_sms(): void
    {
        $view = file_get_contents(resource_path('views/admin/users/index.blade.php'));
        $form = file_get_contents(resource_path('views/admin/users/partials/token-form.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/Admin/AdminUserController.php'));
        $migration = file_get_contents(database_path('migrations/2026_08_31_120000_create_user_token_grants_table.php'));

        $this->assertStringContainsString('bulk-user-token-dialog', $view);
        $this->assertStringContainsString('openBulkUserTokenDialog', $view);
        $this->assertStringContainsString('send_sms', $view);
        $this->assertStringContainsString('tkExpiryUnit', $form);
        $this->assertStringContainsString('expires_at', $controller);
        $this->assertStringContainsString('bulkUpdateToken', $controller);
        $this->assertStringContainsString('resendTokenSms', $controller);
        $this->assertStringContainsString('remaining_amount', $migration);
        $this->assertStringContainsString('expires_at', $migration);
    }
}
