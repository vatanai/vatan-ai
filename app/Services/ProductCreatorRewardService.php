<?php

namespace App\Services;

use App\Models\GeneratedImage;
use App\Models\GeneratedVideo;
use App\Models\Product;
use App\Models\ProductCreatorRewardEvent;
use App\Models\TokenLog;
use App\Models\User;
use App\Services\Finance\FinanceCaseLedgerService;
use Illuminate\Support\Facades\DB;

/**
 * ثبت پاداش مستقل مالک محصول پس از ساخت موفق هر خروجی.
 *
 * این سرویس عمداً به رفرال، رضایت‌نامه یا هزینه‌ی اجرای کاربر وابسته نیست؛
 * تنها منبع تعیین پاداش، تنظیمات خود محصول و سهم اعتبار تسویه‌شده است.
 */
class ProductCreatorRewardService
{
    public function rewardForImage(GeneratedImage $image, array $reservation): ?ProductCreatorRewardEvent
    {
        $image->loadMissing(['product', 'user', 'order']);

        return $this->reward(
            $image->product,
            $image->user_id,
            'photo',
            (int) $image->getKey(),
            $image->order_id,
            $reservation,
        );
    }

    public function rewardForVideo(GeneratedVideo $video, ?array $reservation = null): ?ProductCreatorRewardEvent
    {
        $video->loadMissing(['product', 'user', 'order']);

        return $this->reward(
            $video->product,
            $video->user_id,
            'video',
            (int) $video->getKey(),
            $video->order_id,
            (array) ($reservation ?? $video->credit_reservation ?? []),
        );
    }

    private function reward(
        ?Product $product,
        ?int $consumerId,
        string $mediaType,
        int $outputId,
        ?int $orderId,
        array $reservation,
    ): ?ProductCreatorRewardEvent {
        if (! $product || ! $product->creator_reward_enabled || $outputId < 1) {
            return null;
        }

        $product->loadMissing('creatorRewardOwner');
        $owner = $product->creatorRewardOwner;
        if (! $owner) {
            return null;
        }

        $promotional = max(0, (int) ($reservation['promotional'] ?? 0));
        $paid = max(0, (int) ($reservation['paid'] ?? 0));
        if (($promotional + $paid) < 1) {
            return null;
        }

        $source = match (true) {
            $promotional > 0 && $paid > 0 => 'mixed',
            $paid > 0 => 'paid',
            default => 'free',
        };
        $settings = array_merge([
            'image_free' => 1,
            'video_free' => 2,
            'image_paid' => 2,
            'video_paid' => 4,
        ], (array) $product->creator_reward_settings);
        $settingsMedia = $mediaType === 'photo' ? 'image' : 'video';
        $settingsKey = $settingsMedia . '_' . ($paid > 0 ? 'paid' : 'free');
        $rewardCredits = max(0, (int) ($settings[$settingsKey] ?? 0));
        if ($rewardCredits < 1) {
            return null;
        }

        $eventKey = 'product-creator-reward:' . $mediaType . ':' . $outputId;
        $event = DB::transaction(function () use (
            $product,
            $owner,
            $consumerId,
            $mediaType,
            $outputId,
            $orderId,
            $reservation,
            $promotional,
            $paid,
            $source,
            $settingsKey,
            $rewardCredits,
            $eventKey,
        ): ProductCreatorRewardEvent {
            $existing = ProductCreatorRewardEvent::query()
                ->where('event_key', $eventKey)
                ->lockForUpdate()
                ->first();
            if ($existing?->status === 'credited') {
                return $existing;
            }

            $event = $existing ?: ProductCreatorRewardEvent::query()->create([
                'product_id' => $product->getKey(),
                'owner_user_id' => $owner->getKey(),
                'consumer_user_id' => $consumerId,
                'generation_id' => $outputId,
                'generated_image_id' => $mediaType === 'photo' ? $outputId : null,
                'generated_video_id' => $mediaType === 'video' ? $outputId : null,
                'order_id' => $orderId,
                'event_key' => $eventKey,
                'media_type' => $mediaType,
                'credit_source' => $source,
                'reward_credits' => $rewardCredits,
                'status' => 'pending',
                'metadata' => [
                    'trigger' => 'successful_output',
                    'settings_key' => $settingsKey,
                    'promotional_credits' => $promotional,
                    'paid_credits' => $paid,
                    'product_name' => $product->name_fa ?: $product->name,
                ],
            ]);

            $lockedOwner = User::query()->lockForUpdate()->findOrFail($owner->getKey());
            $balanceBefore = (int) $lockedOwner->tokens;
            $balanceAfter = $balanceBefore + $rewardCredits;
            $lockedOwner->tokens = $balanceAfter;
            $lockedOwner->promotional_tokens = (int) $lockedOwner->promotionalTokenBalance() + $rewardCredits;
            $lockedOwner->save();

            $tokenLog = TokenLog::query()->create([
                'user_id' => $lockedOwner->getKey(),
                'admin_id' => null,
                'action' => 'add',
                'source' => 'product_creator_reward',
                'event_key' => $eventKey,
                'amount' => $rewardCredits,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'note' => 'پاداش ساخت موفق محصول «' . ($product->name_fa ?: $product->name) . '» برای مالک محصول ثبت شد.',
                'metadata' => [
                    'is_promotional' => true,
                    'credit_kind' => 'product_creator_reward',
                    'reward_event_id' => $event->getKey(),
                    'product_id' => $product->getKey(),
                    'output_id' => $outputId,
                    'media_type' => $mediaType,
                    'credit_source' => $source,
                    'promotional_credits' => $promotional,
                    'paid_credits' => $paid,
                ],
            ]);

            app(TokenGrantService::class)->create(
                $lockedOwner,
                $rewardCredits,
                null,
                null,
                $tokenLog->getKey(),
                'product_creator_reward',
            );

            $event->update([
                'status' => 'credited',
                'reward_credits' => $rewardCredits,
                'metadata' => array_merge((array) $event->metadata, [
                    'token_log_id' => $tokenLog->getKey(),
                    'owner_balance_before' => $balanceBefore,
                    'owner_balance_after' => $balanceAfter,
                    'credited_at' => now()->toIso8601String(),
                ]),
            ]);

            return $event->fresh();
        });

        try {
            $tokenLogId = (int) data_get($event->metadata, 'token_log_id');
            if ($tokenLogId > 0) {
                $tokenLog = TokenLog::query()->find($tokenLogId);
                if ($tokenLog) {
                    app(FinanceCaseLedgerService::class)->recordManualCredit($tokenLog);
                }
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $event;
    }
}
