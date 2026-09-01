<?php

namespace App\Services;

use App\Models\Product;
use App\Models\TelegramProductClick;
use App\Models\TelegramUser;
use App\Models\ReferralSetting;
use App\Support\PhoneNumber;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** منطق مستقل جریان بات؛ ارسال واقعی پیام‌ها را لایه‌ی n8n یا درگاه بات انجام می‌دهد. */
class TelegramFlowService
{
    public function __construct(
        private readonly TelegramIdentityService $identity,
        private readonly TelegramDeepLinkService $deepLinks,
        private readonly TelegramContentService $content,
        private readonly TelegramMembershipService $membershipService,
    ) {
    }

    public function handle(array $input): array
    {
        $telegram = (array) ($input['telegram'] ?? []);
        $chatId = (string) ($input['chat_id'] ?? $telegram['id'] ?? '');
        $event = (string) ($input['event'] ?? $this->detectEvent($input));
        $updateId = isset($input['update_id']) ? (int) $input['update_id'] : null;
        if (! isset($input['launch_token']) && isset($input['callback_data'])) {
            $input['launch_token'] = explode(':', (string) $input['callback_data'], 2)[1] ?? null;
        }
        $telegramUser = $this->identity->upsert(
            $telegram,
            $updateId,
            $event,
            $input['payload'] ?? $input,
            $chatId,
        );

        if ($telegramUser->is_blocked) {
            return [
                'ok' => true,
                'type' => 'ignored',
                'reason' => 'blocked_user',
                'chat_id' => $chatId,
            ];
        }

        if ($event === 'start') {
            $payload = $this->deepLinks->decode($input['start_payload'] ?? data_get($input, 'message.text'));
            $click = $this->registerProductClick($telegramUser, $payload, $input);
            if ($click) {
                $telegramUser->setRelation('latestProductClick', $click);
            }

            return $this->afterMembership($telegramUser, $chatId, $input, $click);
        }

        if ($event === 'membership_check') {
            $click = $this->latestClick($telegramUser, $input['launch_token'] ?? null);
            return $this->afterMembership($telegramUser, $chatId, $input, $click);
        }

        if ($event === 'register') {
            $telegramUser->forceFill(['registration_state' => 'awaiting_name'])->save();
            return $this->contentMessage($chatId, 'registration_name', $this->variables($telegramUser));
        }

        if ($event === 'build') {
            $click = $this->latestClick($telegramUser, $input['launch_token'] ?? null);
            return $this->buildResponse($telegramUser, $chatId, $click);
        }

        if ($event === 'all_products') {
            return $this->allProductsResponse($telegramUser, $chatId);
        }

        if ($event === 'contact') {
            $contact = (array) ($input['contact'] ?? []);
            if ((int) ($contact['user_id'] ?? 0) !== (int) $telegramUser->telegram_id) {
                throw ValidationException::withMessages(['phone' => 'شماره باید از حساب تلگرام خودتان ارسال شود.']);
            }
            $user = $this->identity->linkTrustedContact(
                $telegramUser,
                (string) ($contact['phone_number'] ?? ''),
                $telegramUser->first_name,
                $telegramUser->last_name,
            );
            return $this->registeredResponse($telegramUser->fresh(), $chatId, $user, $input);
        }

        if ($event === 'message') {
            return $this->handleText($telegramUser, $chatId, (string) ($input['text'] ?? ''));
        }

        return $this->message($chatId, $this->content->text('welcome', $this->variables($telegramUser)), $this->homeButtons($telegramUser));
    }

    private function afterMembership(TelegramUser $telegramUser, string $chatId, array $input, ?TelegramProductClick $click): array
    {
        $settings = ReferralSetting::current();
        $membership = $input['is_channel_member'] ?? null;
        if ($settings->telegram_membership_required && $membership === null) {
            $membership = $this->membershipService->check(
                (int) $telegramUser->telegram_id,
                $settings->telegram_channel_id
                    ?: $settings->telegram_channel_username
                    ?: config('services.telegram.channel_id')
                    ?: config('services.telegram.channel_username'),
            );
        }

        if ($settings->telegram_membership_required && ! filter_var($membership, FILTER_VALIDATE_BOOLEAN)) {
            $channel = ltrim((string) ($settings->telegram_channel_username ?: config('services.telegram.channel_username', 'ai_vatan')), '@');
            $channelUrl = trim((string) ($settings->telegram_channel_invite_url ?: config('services.telegram.channel_invite_url')));
            $channelUrl = $channelUrl !== '' ? $channelUrl : 'https://t.me/' . $channel;
            $response = $this->contentMessage($chatId, 'membership_required', [
                'channel_url' => $channelUrl,
                'launch_token' => $click?->launch_token ?: '',
            ], [
                ['text' => 'عضویت در کانال وطن', 'url' => $channelUrl],
                ['text' => 'بررسی عضویت', 'callback_data' => 'membership_check' . ($click?->launch_token ? ':' . $click->launch_token : '')],
            ]);
            $response['buttons'] = $this->normalizeMembershipButtons($response['buttons'], $channelUrl, $click?->launch_token);
            return $response;
        }

        if ($telegramUser->user_id) {
            return $this->productOrWelcome($telegramUser, $chatId, $click, true);
        }

        if ($click?->product) {
            return $this->productMessage($chatId, $click, [
                ['text' => 'ثبت‌نام و ساخت با همین محصول', 'callback_data' => 'register'],
            ]);
        }

        return $this->contentMessage($chatId, 'welcome', $this->variables($telegramUser), [
            ['text' => 'ثبت‌نام برای شروع', 'callback_data' => 'register'],
        ]);
    }

    private function handleText(TelegramUser $telegramUser, string $chatId, string $text): array
    {
        $text = trim($text);
        if ($telegramUser->registration_state === 'awaiting_name') {
            [$name, $lastName] = $this->splitName($text);
            if ($name === '') {
                throw ValidationException::withMessages(['name' => 'نام و نام خانوادگی را کامل وارد کنید.']);
            }
            $telegramUser->forceFill([
                'registration_state' => 'awaiting_phone',
                'registration_payload' => ['name' => $name, 'last_name' => $lastName],
            ])->save();
            return $this->contentMessage($chatId, 'registration_phone', [], [
                ['text' => 'ارسال شماره موبایل', 'request_contact' => true],
            ]);
        }

        if ($telegramUser->registration_state === 'awaiting_phone') {
            $phone = PhoneNumber::normalize($text);
            if (! PhoneNumber::isValid($phone)) {
                throw ValidationException::withMessages(['phone' => 'شماره موبایل را درست وارد کنید یا با دکمه ارسال کنید.']);
            }
            $this->identity->requestPhoneOtp($telegramUser, $phone);
            return $this->contentMessage($chatId, 'registration_otp');
        }

        if ($telegramUser->registration_state === 'awaiting_otp') {
            $payload = (array) $telegramUser->registration_payload;
            $user = $this->identity->verifyPhoneOtp(
                $telegramUser,
                $text,
                $payload['name'] ?? $telegramUser->first_name,
                $payload['last_name'] ?? $telegramUser->last_name,
            );
            return $this->registeredResponse($telegramUser->fresh(), $chatId, $user, []);
        }

        return $this->contentMessage($chatId, 'welcome', $this->variables($telegramUser), $this->homeButtons($telegramUser));
    }

    private function registeredResponse(TelegramUser $telegramUser, string $chatId, $user, array $input): array
    {
        $click = $this->latestClick($telegramUser, $input['launch_token'] ?? null);
        $gift = (bool) data_get($telegramUser->metadata, 'site_user_created_from_telegram')
            ? (int) data_get($telegramUser->metadata, 'last_registration_gift', 0)
            : 0;
        $variables = $this->variables($telegramUser) + [
            'gift_tokens' => $gift > 0 ? number_format($gift) : '',
            'launch_token' => $click?->launch_token ?: '',
        ];

        return $this->contentMessage($chatId, 'registration_done', $variables, [
            ['text' => 'ساخت با همین محصول', 'callback_data' => 'build:' . ($click?->launch_token ?? '')],
            ['text' => 'نمایش همه قالب‌ها', 'callback_data' => 'all_products'],
        ]);
    }

    private function productOrWelcome(TelegramUser $telegramUser, string $chatId, ?TelegramProductClick $click, bool $returning = false): array
    {
        if ($click?->product) {
            return $this->productMessage($chatId, $click, [
                ['text' => 'ساخت با همین محصول', 'callback_data' => 'build:' . $click->launch_token],
                ['text' => 'نمایش همه قالب‌ها', 'callback_data' => 'all_products'],
            ]);
        }

        return $this->contentMessage($chatId, $returning ? 'returning_user' : 'welcome', $this->variables($telegramUser), $this->homeButtons($telegramUser));
    }

    private function buildResponse(TelegramUser $telegramUser, string $chatId, ?TelegramProductClick $click): array
    {
        if (! $telegramUser->user_id) {
            return $this->contentMessage($chatId, 'registration_name', [], [
                ['text' => 'ثبت‌نام', 'callback_data' => 'register'],
            ]);
        }
        if (! $click?->product) {
            return $this->contentMessage($chatId, 'product_missing', [], [
                ['text' => 'نمایش همه قالب‌ها', 'callback_data' => 'all_products'],
            ]);
        }

        $click->forceFill(['opened_at' => now()])->save();
        $url = $this->miniAppUrl($click->launch_token);
        return $this->message($chatId, $this->content->text('build_ready'), [[
            'text' => 'ساخت محصول در وطن',
            'web_app' => ['url' => $url],
        ]]) + [
            'launch_token' => $click->launch_token,
            'url' => $url,
            'product_key' => $click->product_key,
            'user_id' => $telegramUser->user_id,
        ];
    }

    private function allProductsResponse(TelegramUser $telegramUser, string $chatId): array
    {
        if (! $telegramUser->user_id) {
            return $this->contentMessage($chatId, 'registration_name', [], [
                ['text' => 'ثبت‌نام', 'callback_data' => 'register'],
            ]);
        }

        $url = $this->miniAppUrl(null, true);
        return $this->message($chatId, $this->content->text('all_products_ready'), [[
            'text' => 'نمایش قالب‌ها در وطن',
            'web_app' => ['url' => $url],
        ]]) + [
            'url' => $url,
            'user_id' => $telegramUser->user_id,
        ];
    }

    private function registerProductClick(TelegramUser $telegramUser, array $payload, array $input): ?TelegramProductClick
    {
        $productKey = trim((string) ($payload['product'] ?? ''));
        $product = null;
        if ($productKey !== '') {
            $product = Product::query()->where('status', 'active')
                ->where(fn ($query) => $query->where('route_slug', $productKey)->orWhere('slug', $productKey))
                ->first();
        }
        return $this->identity->recordProductClick($telegramUser, $product, [
            'product_key' => $productKey ?: null,
            'source' => $payload['source'] ?? 'direct',
            'source_channel' => $payload['channel'] ?? null,
            'source_campaign' => $payload['campaign'] ?? null,
            'message_id' => $payload['message'] ?? data_get($input, 'message.message_id'),
            'channel_id' => data_get($input, 'message.chat.id', data_get($input, 'payload.message.chat.id')),
            'channel_username' => data_get($input, 'message.chat.username', data_get($input, 'payload.message.chat.username')),
            'start_payload' => $input['start_payload'] ?? null,
            'metadata' => ['payload' => $payload],
        ]);
    }

    private function latestClick(TelegramUser $telegramUser, ?string $launchToken = null): ?TelegramProductClick
    {
        return $launchToken
            ? $telegramUser->productClicks()->where('launch_token', $launchToken)->first()
            : $telegramUser->productClicks()->with('product')->latest('clicked_at')->first();
    }

    private function productMessage(string $chatId, TelegramProductClick $click, array $fallbackButtons): array
    {
        $product = $click->product;
        $variables = [
            'product_name' => $product?->name_fa ?: $product?->name_en ?: 'محصول وطن',
            'product_description' => $product?->description_fa ?: $product?->description_en ?: 'برای ساخت، دکمه‌ی زیر رو بزن.',
            'launch_token' => $click->launch_token,
        ];
        $imageUrl = $product?->displayImageUrl();
        $media = $imageUrl && ! Str::startsWith($imageUrl, 'data:')
            ? ['type' => 'photo', 'url' => $imageUrl]
            : null;

        return $this->contentMessage($chatId, 'product_selected', $variables, $fallbackButtons, $media);
    }

    private function miniAppUrl(?string $launchToken, bool $all = false): string
    {
        $base = trim((string) (ReferralSetting::current()->telegram_mini_app_url ?: config('services.telegram.mini_app_url') ?: route('telegram.mini-app')));
        $query = array_filter(['launch' => $launchToken, 'all' => $all ? '1' : null]);
        return $base . ($query ? '?' . http_build_query($query) : '');
    }

    private function message(string $chatId, string $text, array $buttons = []): array
    {
        return ['ok' => true, 'type' => 'send_message', 'chat_id' => $chatId, 'text' => $text, 'buttons' => $buttons];
    }

    private function contentMessage(string $chatId, string $key, array $variables = [], array $fallbackButtons = [], ?array $fallbackMedia = null): array
    {
        $response = $this->message(
            $chatId,
            $this->content->text($key, $variables),
            $this->content->buttons($key, $variables, $fallbackButtons),
        );
        $media = $this->content->media($key, $variables) ?: $fallbackMedia;
        if ($media) {
            $response['media'] = $media;
        }

        return $response;
    }

    private function homeButtons(TelegramUser $telegramUser): array
    {
        return $telegramUser->user_id
            ? [['text' => 'نمایش همه قالب‌ها', 'callback_data' => 'all_products']]
            : [['text' => 'ثبت‌نام برای شروع', 'callback_data' => 'register']];
    }

    private function normalizeMembershipButtons(array $buttons, string $channelUrl, ?string $launchToken): array
    {
        return array_map(function (array $button) use ($channelUrl, $launchToken): array {
            if (($button['action'] ?? null) === 'join_channel') {
                unset($button['action']);
                $button['url'] = $channelUrl;
            }
            if (($button['callback_data'] ?? null) === 'membership_check' && $launchToken) {
                $button['callback_data'] .= ':' . $launchToken;
            }
            return $button;
        }, $buttons);
    }

    private function variables(TelegramUser $telegramUser): array
    {
        return ['first_name' => $telegramUser->first_name ?: 'دوست وطن'];
    }

    private function splitName(string $value): array
    {
        $parts = preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $name = array_shift($parts) ?: '';
        return [$name, trim(implode(' ', $parts))];
    }

    private function detectEvent(array $input): string
    {
        if (isset($input['start_payload']) || str_starts_with((string) data_get($input, 'message.text', ''), '/start')) return 'start';
        if (isset($input['contact'])) return 'contact';
        if (isset($input['callback_data'])) return explode(':', (string) $input['callback_data'], 2)[0];
        return 'message';
    }
}
