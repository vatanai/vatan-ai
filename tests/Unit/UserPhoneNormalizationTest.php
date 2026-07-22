<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserPhoneNormalizationTest extends TestCase
{
    #[DataProvider('phoneVariants')]
    public function test_phone_variants_are_stored_as_one_identity(string $input): void
    {
        $user = new User(['phone' => $input]);

        self::assertSame('09121234567', $user->phone);
    }

    public static function phoneVariants(): array
    {
        return [
            ['09121234567'],
            ['9121234567'],
            ['+989121234567'],
            ['00989121234567'],
            ['۰۹۱۲۱۲۳۴۵۶۷'],
            ['0912 123 4567'],
        ];
    }
}
