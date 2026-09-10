<?php

namespace App\Support;

final class ReferralCode
{
    /** کد کوتاه شامل حروف انگلیسی و عدد؛ برای لینک‌های جدید استفاده می‌شود. */
    public static function five(): string
    {
        $letters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $digits = '23456789';
        $all = $letters . $digits;
        $characters = [
            $letters[random_int(0, strlen($letters) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
        ];

        for ($index = 2; $index < 5; $index++) {
            $characters[] = $all[random_int(0, strlen($all) - 1)];
        }

        shuffle($characters);

        return implode('', $characters);
    }
}
