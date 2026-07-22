<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\TokenBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TokenBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_balance_credit_and_debit_use_the_users_token_column(): void
    {
        $user = User::factory()->create(['tokens' => 50, 'tokens_used' => 0, 'tokens_purchased' => 0]);
        $service = app(TokenBalanceService::class);

        $service->credit($user, 20, purchased: true);
        $service->debit($user, 15);

        $user->refresh();
        self::assertSame(55, $user->token_balance);
        self::assertSame(20, $user->tokens_purchased);
        self::assertSame(15, $user->tokens_used);
    }

    public function test_debit_never_allows_a_negative_balance(): void
    {
        $user = User::factory()->create(['tokens' => 4]);

        $this->expectException(ValidationException::class);
        app(TokenBalanceService::class)->debit($user, 5);

        self::assertSame(4, $user->fresh()->token_balance);
    }
}
