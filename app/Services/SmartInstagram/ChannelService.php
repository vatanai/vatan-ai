<?php

namespace App\Services\SmartInstagram;

use App\Models\MarketingIntegration;
use App\Models\SmartInstagram\Channel;
use App\Services\SmartInstagram\Gateways\GatewayManager;
use Illuminate\Support\Facades\Schema;

/** مرکز اتصال‌ها (پروپوزال ۵.۱): کانال‌ها، همگام‌سازی با اتصال Meta موجود و تست سلامت. */
class ChannelService
{
    public function __construct(
        private readonly WorkspaceContext $context,
        private readonly GatewayManager $gateways,
        private readonly OperationLogger $logger,
    ) {
    }

    /** کانال متناظر رویداد؛ اگر هنوز کانالی ثبت نشده، از اتصال Meta موجود ساخته می‌شود. */
    public function resolve(?string $accountId): Channel
    {
        $query = Channel::query()->where('workspace_id', $this->context->id());
        if ($accountId) {
            $match = (clone $query)->where('external_account_id', $accountId)->first();
            if ($match) {
                return $match;
            }
        }

        return (clone $query)->where('gateway', 'meta')->orderBy('id')->first()
            ?? (clone $query)->orderBy('id')->first()
            ?? $this->syncFromMetaIntegration()
            ?? Channel::query()->create([
                'workspace_id' => $this->context->id(),
                'gateway' => 'meta',
                'name' => 'اینستاگرام وطن',
                'external_account_id' => $accountId,
                'status' => 'pending',
            ]);
    }

    /** کانال اصلی را با اتصال Meta ثبت‌شده در «تکنولوژی مارکتینگ» هم‌گام می‌کند (بدون کپی‌کردن توکن). */
    public function syncFromMetaIntegration(): ?Channel
    {
        if (!Schema::hasTable('marketing_integrations')) {
            return null;
        }

        $integration = MarketingIntegration::query()->where('provider', 'meta')->first();
        if (!$integration) {
            return null;
        }

        $credentials = (array) $integration->credentials;
        $settings = (array) $integration->settings;

        $channel = Channel::query()->firstOrNew([
            'workspace_id' => $this->context->id(),
            'marketing_integration_id' => $integration->id,
        ]);
        $channel->fill([
            'gateway' => $channel->gateway ?: 'meta',
            'name' => $channel->name ?: ($integration->name ?: 'اینستاگرام وطن'),
            'external_account_id' => $credentials['instagram_user_id'] ?? $channel->external_account_id,
            'username' => $settings['username'] ?? data_get($settings, 'profile.username', $channel->username),
            'status' => $integration->status === 'connected' ? 'connected' : ($channel->status ?: 'pending'),
            'last_error' => $integration->last_error,
        ]);
        $channel->save();

        return $channel;
    }

    /** کانال اینستاگرام متصل در Composio را بدون ذخیره‌ی کلید API ثبت می‌کند. */
    public function syncFromComposio(): ?Channel
    {
        if (!(bool) config('smart_instagram.composio.enabled')
            || !filled(config('services.composio.connected_account_id'))
            || !filled(config('services.composio.user_id'))) {
            return null;
        }

        $settings = [
            'composio_connected_account_id' => (string) config('services.composio.connected_account_id'),
            'composio_user_id' => (string) config('services.composio.user_id'),
            'composio_instagram_user_id' => (string) config('services.composio.instagram_user_id', 'me'),
        ];
        $channel = Channel::query()->firstOrNew([
            'workspace_id' => $this->context->id(),
            'gateway' => 'composio',
        ]);
        $channel->fill([
            'name' => $channel->name ?: 'اینستاگرام وطن (Composio)',
            'external_account_id' => $channel->external_account_id ?: $settings['composio_instagram_user_id'],
            'username' => $channel->username ?: 'ai_vatan',
            'status' => $channel->status === 'error' ? 'pending' : ($channel->status ?: 'connected'),
            'outbound_enabled' => false,
            'settings' => array_merge((array) $channel->settings, $settings),
            'last_error' => null,
        ]);
        $channel->save();

        return $channel;
    }

    public function testHealth(Channel $channel): array
    {
        try {
            $result = $this->gateways->for($channel)->health($channel);
        } catch (\Throwable $e) {
            $result = \App\Services\SmartInstagram\Gateways\GatewayResult::failure($e->getMessage());
        }

        $profile = (array) ($result->data['profile'] ?? []);
        $channel->forceFill([
            'status' => $result->ok ? 'connected' : 'error',
            'health_checked_at' => now(),
            'last_error' => $result->ok ? null : $result->message,
            'username' => $profile['username'] ?? $channel->username,
            'account_type' => $profile['account_type'] ?? $channel->account_type,
        ])->save();

        $this->logger->log('channel.health', $result->message, $channel, ['ok' => $result->ok], $result->ok ? 'info' : 'warning');

        return ['ok' => $result->ok, 'message' => $result->message, 'profile' => $profile, 'media' => $result->data['media'] ?? []];
    }
}
