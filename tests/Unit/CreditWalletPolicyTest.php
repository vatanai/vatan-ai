<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\User;
use App\Services\CreditWalletService;
use App\Services\ModelTierService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreditWalletPolicyTest extends TestCase
{
    public function test_grade_three_and_four_reservations_consume_promotional_credit_first(): void
    {
        $allocation = (new CreditWalletService())->allocationForReservation(
            balance: 10,
            promotionalBalance: 6,
            amount: 8,
            allowPromotionalCredits: true,
        );

        self::assertSame(['total' => 8, 'promotional' => 6, 'paid' => 2], $allocation);
    }

    public function test_grade_one_and_two_reservations_reject_a_promotional_only_balance(): void
    {
        $this->expectException(ValidationException::class);

        (new CreditWalletService())->allocationForReservation(
            balance: 10,
            promotionalBalance: 10,
            amount: 1,
            allowPromotionalCredits: false,
        );
    }

    public function test_paid_plan_can_use_its_remaining_promotional_balance_for_every_quality(): void
    {
        $user = new User();
        $user->setRelation('plan', new Plan([
            'billing_type' => 'one_time',
            'price' => 970000,
        ]));

        $allowPromotionalCredits = app(ModelTierService::class)->hasPaidPlan($user);
        $allocation = app(CreditWalletService::class)->allocationForReservation(
            balance: 50,
            promotionalBalance: 50,
            amount: 20,
            allowPromotionalCredits: $allowPromotionalCredits,
        );

        $this->assertTrue($allowPromotionalCredits);
        $this->assertSame(['total' => 20, 'promotional' => 20, 'paid' => 0], $allocation);
    }
}
