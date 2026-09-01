<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\Product;
use App\Models\TelegramEvent;
use App\Models\TelegramProductClick;
use App\Models\TelegramUser;
use App\Models\TokenLog;
use App\Models\User;
use App\Models\ReferralSetting;
use App\Support\PhoneNumber;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TelegramIdentityService
{
    public function upsert(array $telegram, ?int $updateId = null, ?string $eventType = null, array $payload = [], ?string $chatId = null): TelegramUser
    {
        $telegramId = (int) ($telegram['id'] ?? 0);
        if ($telegramId < 1) {
            throw ValidationException::withMessages(['telegram_id' => 'شناسه‌ی کاربر تلگرام معتبر نیست.']);
        }

        $user = TelegramUser::query()->firstOrNew(['telegram_id' => $telegramId]);
        $user->fill([
            'username' => $telegram['username'] ?? $user->username,
            'first_name' => $telegram['first_name'] ?? $user->first_name,
            'last_name' => $telegram['last_name'] ?? $user->last_name,
            'language_code' => $telegram['language_code'] ?? $user->language_code,
            'is_premium' => (bool) ($telegram['is_premium'] ?? $user->is_premium),
            'started_at' => $eventType === 'start' ? ($user->started_at ?: now()) : $user->started_at,
            'last_active_at' => now(),
        ]);
        $user->save();

        if ($eventType) {
            $this->recordEvent($user, $eventType, $payload, $updateId, $chatId);
        }

        return $user->fresh();
    }

    public function recordEvent(TelegramUser $user, string $eventType, array $payload = [], ?int $updateId = null, ?string $chatId = null): ?TelegramEvent
    {
        try {
            return TelegramEvent::query()->create([
                'update_id' => $updateId,
                'telegram_user_id' => $user->id,
                'event_type' => Str::limit($eventType, 80, ''),
                'chat_id' => $chatId,
                'payload' => $payload,
                'occurred_at' => now(),
            ]);
        } catch (QueryException $exception) {
            // update_id تکراری یعنی تلگرام همان رویداد را دوباره فرستاده است.
            if ($updateId !== null) {
                return TelegramEvent::query()->where('update_id', $updateId)->first();
            }
            throw $exception;
        }
    }

    public function recordProductClick(TelegramUser $user, ?Product $product, array $context = []): TelegramProductClick
    {
        $click = $user->productClicks()->create([
            'launch_token' => (string) Str::uuid(),
            'product_id' => $product?->id,
            'product_key' => $context['product_key'] ?? $product?->route_slug,
            'source' => $context['source'] ?? 'channel',
            'source_channel' => $context['source_channel'] ?? null,
            'source_campaign' => $context['source_campaign'] ?? null,
            'channel_id' => $context['channel_id'] ?? null,
            'channel_username' => $context['channel_username'] ?? null,
            'message_id' => $context['message_id'] ?? null,
            'start_payload' => $context['start_payload'] ?? null,
            'metadata' => $context['metadata'] ?? null,
            'clicked_at' => now(),
        ]);

        $user->forceFill(['last_active_at' => now()])->save();
        return $click;
    }

    public function requestPhoneOtp(TelegramUser $telegramUser, string $phone): void
    {
        $phone = PhoneNumber::normalize($phone);
        if (! PhoneNumber::isValid($phone)) {
            throw ValidationException::withMessages(['phone' => 'شماره موبایل واردشده معتبر نیست.']);
        }

        $key = 'telegram-auth-otp:' . $telegramUser->telegram_id . '|' . $phone;
        if (RateLimiter::tooManyAttempts($key, 1)) {
            throw ValidationException::withMessages(['phone' => 'کد قبلاً ارسال شده است؛ کمی بعد دوباره تلاش کنید.']);
        }

        $existing = User::query()->where('phone', $phone)->first();
        if ($existing?->status === 'suspended' || $existing?->status === 'deleted') {
            throw ValidationException::withMessages(['phone' => 'امکان اتصال این حساب وجود ندارد.']);
        }

        $code = (string) random_int(10000, 99999);
        $sent = app(SmsEventService::class)->send($existing ? 'login_otp' : 'otp_code', $phone, [
            'name' => trim((string) ($existing?->name ?: $telegramUser->first_name)) ?: 'کاربر',
            'code' => $code,
            'expiry_minutes' => '3',
            'brand_name' => 'پلتفرم وطن',
        ], type: 'telegram_authentication');

        if (! $sent) {
            throw ValidationException::withMessages(['phone' => 'ارسال کد انجام نشد؛ دوباره تلاش کنید.']);
        }

        Otp::query()->where('phone', $phone)->where('purpose', 'telegram_auth')->where('used', false)->update(['used' => true]);
        Otp::query()->create([
            'phone' => $phone,
            'purpose' => 'telegram_auth',
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(3),
            'used' => false,
            'attempts' => 0,
        ]);
        RateLimiter::hit($key, (int) config('services.telegram.otp_resend_seconds', 60));

        $telegramUser->forceFill([
            'registration_state' => 'awaiting_otp',
            'registration_payload' => ['phone' => $phone],
        ])->save();
    }

    public function verifyPhoneOtp(TelegramUser $telegramUser, string $code, ?string $name = null, ?string $lastName = null): User
    {
        $phone = PhoneNumber::normalize(data_get($telegramUser->registration_payload, 'phone'));
        if (! PhoneNumber::isValid($phone) || ! preg_match('/^\d{5}$/', trim($code))) {
            throw ValidationException::withMessages(['code' => 'کد واردشده معتبر نیست.']);
        }

        $otp = Otp::query()->where('phone', $phone)->where('purpose', 'telegram_auth')->where('used', false)->latest()->first();
        if (! $otp || ! $otp->isValid() || $otp->attempts >= 5) {
            throw ValidationException::withMessages(['code' => 'کد منقضی یا نامعتبر است.']);
        }

        $otp->increment('attempts');
        if (! Hash::check(trim($code), $otp->code)) {
            throw ValidationException::withMessages(['code' => 'کد واردشده اشتباه است.']);
        }

        $otp->update(['used' => true]);
        return $this->linkVerifiedPhone($telegramUser, $phone, $name, $lastName, false);
    }

    public function linkTrustedContact(TelegramUser $telegramUser, string $phone, ?string $name = null, ?string $lastName = null): User
    {
        return $this->linkVerifiedPhone($telegramUser, $phone, $name, $lastName, true);
    }

    public function linkVerifiedPhone(TelegramUser $telegramUser, string $phone, ?string $name = null, ?string $lastName = null, bool $trustedContact = false): User
    {
        $phone = PhoneNumber::normalize($phone);
        if (! PhoneNumber::isValid($phone)) {
            throw ValidationException::withMessages(['phone' => 'شماره موبایل واردشده معتبر نیست.']);
        }

        return DB::transaction(function () use ($telegramUser, $phone, $name, $lastName, $trustedContact): User {
            $lockedTelegram = TelegramUser::query()->lockForUpdate()->findOrFail($telegramUser->id);
            $user = User::query()->where('phone', $phone)->lockForUpdate()->first();

            if ($user?->status === 'deleted' || $user?->status === 'suspended') {
                throw ValidationException::withMessages(['phone' => 'امکان اتصال این حساب وجود ندارد.']);
            }

            $existingTelegramUser = $user
                ? TelegramUser::query()->where('user_id', $user->id)->first()
                : null;
            if ($existingTelegramUser && (int) $existingTelegramUser->id !== (int) $lockedTelegram->id) {
                throw ValidationException::withMessages(['phone' => 'این حساب سایت قبلاً به حساب تلگرام دیگری متصل شده است.']);
            }

            $isNewSiteUser = ! $user;
            if ($isNewSiteUser) {
                $attributes = [
                    'name' => trim((string) ($name ?: $lockedTelegram->first_name)) ?: null,
                    'last_name' => trim((string) ($lastName ?: $lockedTelegram->last_name)) ?: null,
                    'phone' => $phone,
                    'password' => Str::random(64),
                    'status' => 'active',
                    'tokens' => 0,
                    'tokens_purchased' => 0,
                    'tokens_used' => 0,
                    'promotional_tokens' => 0,
                    'registered_at' => now(),
                    'last_login_at' => now(),
                    'login_count' => 1,
                ];
                $attributes = $this->existingColumnsOnly('users', $attributes);
                $user = User::query()->create($attributes);
            }

            $lockedTelegram->forceFill([
                'user_id' => $user->id,
                'phone' => $phone,
                'phone_verified_at' => now(),
                'registration_state' => 'completed',
                'registration_payload' => null,
                'registration_completed_at' => $lockedTelegram->registration_completed_at ?: now(),
                'last_active_at' => now(),
            ])->save();

            $giftAmount = 0;
            if ($isNewSiteUser) {
                $giftAmount = $this->grantTelegramRegistrationGift($user);
            } elseif (Schema::hasColumn('users', 'telegram_gift_claimed_at') && ! $user->telegram_gift_claimed_at) {
                // کاربرانی که قبلاً در سایت ثبت‌نام کرده‌اند، واجد هدیه‌ی ثبت‌نام تلگرام نیستند.
                $user->forceFill(['telegram_gift_claimed_at' => now()])->save();
            }

            $lockedTelegram->forceFill([
                'metadata' => array_merge((array) $lockedTelegram->metadata, [
                    'site_user_created_from_telegram' => $isNewSiteUser,
                    'last_registration_gift' => $giftAmount,
                ]),
            ])->save();

            return $user->fresh();
        });
    }

    public function grantTelegramRegistrationGift(User $user): int
    {
        return DB::transaction(function () use ($user): int {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $settings = ReferralSetting::current();
            $claimedColumn = Schema::hasColumn('users', 'telegram_gift_claimed_at');
            if ($claimedColumn && $locked->telegram_gift_claimed_at) {
                return 0;
            }

            $amount = $settings->telegram_registration_gift_enabled
                ? max(0, (int) $settings->telegram_registration_gift_tokens)
                : 0;

            if ($amount > 0) {
                $eventKey = 'telegram-registration-gift:' . $locked->id;
                if (Schema::hasTable('token_logs') && TokenLog::query()->where('event_key', $eventKey)->exists()) {
                    if ($claimedColumn) $locked->forceFill(['telegram_gift_claimed_at' => now()])->save();
                    return 0;
                }

                $before = (int) $locked->tokens;
                $locked->forceFill([
                    'tokens' => $before + $amount,
                    'promotional_tokens' => (int) $locked->promotional_tokens + $amount,
                ])->save();

                $tokenLog = null;
                if (Schema::hasTable('token_logs')) {
                    $tokenLog = TokenLog::query()->create([
                        'user_id' => $locked->id,
                        'admin_id' => null,
                        'action' => 'add',
                        'source' => 'telegram_registration_gift',
                        'event_key' => $eventKey,
                        'amount' => $amount,
                        'balance_before' => $before,
                        'balance_after' => $before + $amount,
                        'note' => 'هدیه ثبت‌نام از طریق تلگرام',
                        'metadata' => ['channel' => 'telegram'],
                    ]);
                }

                if (Schema::hasTable('user_token_grants')) {
                    app(TokenGrantService::class)->create($locked, $amount, null, null, $tokenLog?->id, 'telegram_registration_gift');
                }
            }

            if ($claimedColumn) {
                $locked->forceFill(['telegram_gift_claimed_at' => now()])->save();
            }

            return $amount;
        });
    }

    private function existingColumnsOnly(string $table, array $attributes): array
    {
        return collect($attributes)->filter(fn ($value, $key) => Schema::hasColumn($table, $key))->all();
    }
}
