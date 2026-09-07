<?php

namespace Tests\Unit;

use App\Services\Finance\FinanceProfitCalculator;
use PHPUnit\Framework\TestCase;

class FinanceProfitCalculatorTest extends TestCase
{
    public function test_plan_profit_uses_gateway_model_and_allocated_costs(): void
    {
        $result = (new FinanceProfitCalculator())->plan(
            sales: 1_000_000,
            credits: 100,
            modelCostPerCredit: 1_000,
            gatewayPercent: 1,
            infrastructurePercent: 8,
            workforcePercent: 12,
        );

        self::assertSame(10_000.0, $result['gateway_fee_irr']);
        self::assertSame(100_000.0, $result['estimated_model_cost_irr']);
        self::assertSame(890_000.0, $result['gross_profit_irr']);
        self::assertSame(690_000.0, $result['net_profit_irr']);
        self::assertSame(69.0, $result['margin_percent']);
    }

    public function test_order_profit_can_be_negative_without_division_errors(): void
    {
        $result = (new FinanceProfitCalculator())->order(
            allocatedRevenue: 50_000,
            directCost: 70_000,
            infrastructurePercent: 8,
            workforcePercent: 12,
        );

        self::assertSame(-20_000.0, $result['gross_profit_irr']);
        self::assertSame(-30_000.0, $result['net_profit_irr']);
        self::assertSame(-60.0, $result['margin_percent']);
    }
}
