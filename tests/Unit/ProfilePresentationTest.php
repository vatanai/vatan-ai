<?php

namespace Tests\Unit;

use App\Http\Controllers\ProfileController;
use App\Models\Plan;
use App\Models\ReferralSetting;
use App\Models\User;
use ReflectionMethod;
use Tests\TestCase;

class ProfilePresentationTest extends TestCase
{
    public function test_referral_item_is_enabled_by_default(): void
    {
        $this->assertTrue(ReferralSetting::defaults()['profile_enabled']);
    }

    public function test_profile_plan_names_are_reduced_to_the_four_official_labels(): void
    {
        $method = new ReflectionMethod(ProfileController::class, 'profilePlanName');
        $controller = app(ProfileController::class);

        $this->assertSame('رایگان', $method->invoke($controller, new User()));
        $this->assertSame('حرفه‌ای', $method->invoke($controller, $this->userWithPlan('پلن حرفه‌ای', 'pro')));
        $this->assertSame('پیشرفته', $method->invoke($controller, $this->userWithPlan('پیشرفته', 'advanced')));
        $this->assertSame('کسب و کار', $method->invoke($controller, $this->userWithPlan('پیشرفته بیزینس', 'business')));
    }

    private function userWithPlan(string $name, string $tier): User
    {
        $user = new User();
        $user->setRelation('plan', new Plan([
            'name' => $name,
            'slug' => $tier,
            'model_tier_key' => $tier,
            'billing_type' => 'one_time',
            'price' => 100000,
        ]));

        return $user;
    }
}
