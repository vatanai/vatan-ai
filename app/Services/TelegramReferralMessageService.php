<?php

namespace App\Services;

use App\Models\ReferralLink;
use App\Models\TelegramReferralMessage;
use App\Models\TelegramUser;

class TelegramReferralMessageService
{
    public function __construct(
        private readonly ReferralLinkReportService $reports,
        private readonly TelegramInteractionService $telegram,
    ) {
    }

    /** @return array{created:int,updated:int,unchanged:int,failed:int} */
    public function sync(TelegramUser $telegramUser, string $chatId): array
    {
        $telegramUser->loadMissing('user');
        if (! $telegramUser->user_id || ! $telegramUser->user || $chatId === '') {
            return ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'failed' => 0];
        }

        $items = [['key' => 'profile', 'link' => null, 'report' => $this->reports->resolve($telegramUser->user, 'profile')['report']]];
        foreach ($this->reports->linksFor($telegramUser->user) as $link) {
            $items[] = [
                'key' => $link->slug,
                'link' => $link,
                'report' => $this->reports->linkReport($link),
            ];
        }

        $stats = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'failed' => 0];
        $activeKeys = [];

        foreach ($items as $item) {
            $key = (string) $item['key'];
            $activeKeys[] = $key;
            $payload = $this->payload($item['report']);
            $hash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $message = TelegramReferralMessage::query()->firstOrNew([
                'telegram_user_id' => $telegramUser->id,
                'link_key' => $key,
            ]);

            if ($message->exists && $message->content_hash === $hash && $message->chat_id === $chatId) {
                $message->forceFill(['last_synced_at' => now(), 'is_active' => true])->save();
                $stats['unchanged']++;
                continue;
            }

            $result = null;
            if ($message->exists && $message->message_id && $message->chat_id === $chatId) {
                $result = $this->telegram->editText($chatId, (int) $message->message_id, $payload['text'], $payload['buttons']);
                if ($result !== null) {
                    $message->forceFill([
                        'content_hash' => $hash,
                        'last_synced_at' => now(),
                        'is_active' => true,
                    ])->save();
                    $stats['updated']++;
                    continue;
                }
            }

            $result = $this->telegram->sendText($chatId, $payload['text'], $payload['buttons']);
            $messageId = (int) data_get($result, 'message_id', 0);
            if ($messageId < 1) {
                $stats['failed']++;
                continue;
            }

            $message->forceFill([
                'telegram_user_id' => $telegramUser->id,
                'referral_link_id' => $item['link']?->id,
                'link_key' => $key,
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'content_hash' => $hash,
                'last_synced_at' => now(),
                'is_active' => true,
            ])->save();
            $stats[$message->wasRecentlyCreated ? 'created' : 'updated']++;
        }

        TelegramReferralMessage::query()
            ->where('telegram_user_id', $telegramUser->id)
            ->whereNotIn('link_key', $activeKeys)
            ->update(['is_active' => false, 'updated_at' => now()]);

        return $stats;
    }

    /** @return array{text:string,buttons:array<int,array<string,mixed>>} */
    private function payload(array $report): array
    {
        $stats = $report['stats'];
        $link = (string) ($report['slug'] ?? 'profile');
        $text = "📊 گزارش رفرال\n\n"
            . "🎯 " . $report['title'] . "\n"
            . "وضعیت: " . $report['status'] . "\n\n"
            . "👁 کلیک: " . number_format((int) $stats['clicks']) . "\n"
            . "👤 بازدیدکننده یکتا: " . number_format((int) $stats['unique']) . "\n"
            . "📝 ثبت‌نام: " . number_format((int) $stats['registrations']) . "\n"
            . "🛒 خرید موفق: " . number_format((int) $stats['purchases']) . "\n"
            . "🎁 پاداش پرداخت‌شده: " . number_format((int) $stats['paid']) . " اعتبار\n"
            . "⏳ در انتظار: " . number_format((int) $stats['pending']) . " اعتبار\n\n"
            . "نرخ تبدیل: " . $stats['conversion_rate'] . "٪\n"
            . "آخرین همگام‌سازی: همین حالا";

        return [
            'text' => $text,
            'buttons' => [
                [
                    'text' => '📈 مشاهده‌ی داشبورد کامل',
                    'web_app' => ['url' => route('telegram.mini-app', ['target' => 'referral', 'link' => $link])],
                ],
                [
                    'text' => '🔗 اشتراک‌گذاری لینک',
                    'url' => $report['share_url'],
                ],
            ],
        ];
    }
}
