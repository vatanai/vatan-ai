<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('referral_rewards')
            ->where('reward_type', 'registration_gift')
            ->where('status', 'pending')
            ->orderBy('id')
            ->each(function ($reward): void {
                DB::transaction(function () use ($reward): void {
                    $lockedReward = DB::table('referral_rewards')->where('id', $reward->id)->lockForUpdate()->first();
                    if (! $lockedReward || $lockedReward->status !== 'pending') {
                        return;
                    }

                    $user = DB::table('users')->where('id', $lockedReward->user_id)->lockForUpdate()->first();
                    if (! $user) {
                        return;
                    }

                    $before = (int) $user->tokens;
                    $after = $before + (int) $lockedReward->amount;
                    DB::table('users')->where('id', $user->id)->update(['tokens' => $after, 'updated_at' => now()]);
                    DB::table('referral_rewards')->where('id', $lockedReward->id)->update([
                        'status' => 'paid', 'reason' => null, 'balance_before' => $before,
                        'balance_after' => $after, 'processed_at' => now(), 'updated_at' => now(),
                    ]);

                    DB::table('token_logs')->updateOrInsert(
                        ['event_key' => $lockedReward->event_key],
                        [
                            'user_id' => $user->id, 'admin_id' => null, 'action' => 'add',
                            'source' => 'registration_gift', 'amount' => (int) $lockedReward->amount,
                            'balance_before' => $before, 'balance_after' => $after,
                            'note' => 'هدیه ثبت‌نام', 'metadata' => json_encode(['reward_id' => $lockedReward->id]),
                            'created_at' => now(), 'updated_at' => now(),
                        ]
                    );
                });
            });
    }

    public function down(): void
    {
        // اعتبار هدیه مصرف‌شدنی است و برگشت خودکار آن امن نیست.
    }
};
