<?php

namespace Tests\Unit;

use App\Models\Discount;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DiscountCalculationTest extends TestCase
{
    public static function cases(): array
    {
        return [
            'percent' => [['type' => 'percent', 'value' => 20], 50, 10],
            'percent cap' => [['type' => 'percent', 'value' => 50, 'max_discount_credits' => 12], 50, 12],
            'fixed' => [['type' => 'fixed', 'value' => 8], 50, 8],
            'fixed cannot exceed order' => [['type' => 'fixed', 'value' => 80], 50, 50],
            'free' => [['type' => 'free', 'value' => 0], 50, 50],
            'below minimum' => [['type' => 'percent', 'value' => 20, 'min_order_credits' => 60], 50, 0],
        ];
    }

    #[DataProvider('cases')]
    public function test_credit_discount_calculation(array $attributes, int $credits, int $expected): void
    {
        $discount = new Discount(array_merge([
            'min_order_credits' => 0,
            'max_discount_credits' => null,
        ], $attributes));

        self::assertSame($expected, $discount->calculateCredits($credits));
    }
}
