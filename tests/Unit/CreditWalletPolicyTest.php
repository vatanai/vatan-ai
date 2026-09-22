<?php

namespace Tests\Unit;

use App\Services\CreditWalletService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreditWalletPolicyTest extends TestCase
{
    public function test_every_reservation_consumes_promotional_credit_first(): void
    {
        $allocation = (new CreditWalletService())->allocationForReservation(
            balance: 10,
            promotionalBalance: 6,
            amount: 8,
        );

        self::assertSame(['total' => 8, 'promotional' => 6, 'paid' => 2], $allocation);
    }

    public function test_promotional_only_balance_is_spendable(): void
    {
        $allocation = (new CreditWalletService())->allocationForReservation(
            balance: 10,
            promotionalBalance: 10,
            amount: 8,
        );

        self::assertSame(['total' => 8, 'promotional' => 8, 'paid' => 0], $allocation);
    }

    public function test_only_the_unified_total_balance_limits_a_reservation(): void
    {
        try {
            app(CreditWalletService::class)->allocationForReservation(
                balance: 9,
                promotionalBalance: 9,
                amount: 10,
            );
            self::fail('رزرو بیشتر از موجودی کل باید رد شود.');
        } catch (ValidationException $exception) {
            self::assertSame('موجودی اعتبار شما کافی نیست.', $exception->errors()['tokens'][0]);
        }
    }
}
