<?php

namespace App\Services\ProductShots;

use App\Models\ProductShotSetting;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Schema;

/**
 * فیچر فلگ «استودیو محصول».
 *
 * باز است فقط وقتی: کلید env روشن + تنظیم پنل روشن + کاربر در مخاطب باشد.
 * مخاطب: admins (فقط کسی که در همین مرورگر وارد پنل مدیریت است)،
 * whitelist (ادمین + کاربران لیست سفید) یا public (همه).
 */
class ProductShotFeature
{
    private static ?bool $schemaReady = null;

    public static function hasSchema(): bool
    {
        if (self::$schemaReady !== null) {
            return self::$schemaReady;
        }
        try {
            return self::$schemaReady = Schema::hasTable('product_shot_settings')
                && Schema::hasColumn('products', 'product_mode');
        } catch (\Throwable) {
            return self::$schemaReady = false;
        }
    }

    public static function resetSchemaCache(): void
    {
        self::$schemaReady = null;
        ProductShotSetting::forgetCache();
    }

    public function settings(): ProductShotSetting
    {
        return ProductShotSetting::current();
    }

    /** آیا ماژول در کل روشن است (برای نمایش بخش‌های پنل مدیریت). */
    public function enabled(): bool
    {
        return (bool) config('product_shots.enabled', true)
            && self::hasSchema()
            && $this->settings()->enabled;
    }

    public function availableFor(?User $user): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        if ($this->isAdminSession()) {
            return true;
        }

        $settings = $this->settings();

        return match ($settings->audience) {
            'public' => true,
            'whitelist' => $user !== null && $this->isWhitelisted($user, $settings),
            default => false,
        };
    }

    public function isWhitelisted(User $user, ?ProductShotSetting $settings = null): bool
    {
        $settings ??= $this->settings();
        $ids = array_map('intval', (array) $settings->whitelist_user_ids);
        if (in_array((int) $user->id, $ids, true)) {
            return true;
        }

        $phones = array_filter(array_map(fn ($p) => $this->normalizePhone((string) $p), (array) $settings->whitelist_phones));
        $userPhone = $this->normalizePhone((string) $user->phone);

        return $userPhone !== '' && in_array($userPhone, $phones, true);
    }

    private function isAdminSession(): bool
    {
        try {
            return auth('admin')->check();
        } catch (\Throwable) {
            return false;
        }
    }

    private function normalizePhone(string $phone): string
    {
        if (class_exists(PhoneNumber::class) && method_exists(PhoneNumber::class, 'normalize')) {
            try {
                return (string) PhoneNumber::normalize($phone);
            } catch (\Throwable) {
                // ادامه با نرمال‌سازی ساده
            }
        }
        $digits = preg_replace('/\D+/', '', strtr($phone, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']));
        if (str_starts_with($digits, '98')) {
            $digits = '0' . substr($digits, 2);
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0' . $digits;
        }

        return $digits;
    }
}
