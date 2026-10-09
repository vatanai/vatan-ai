<?php

namespace App\Services\SmartInstagram\Posts;

use App\Models\SmartInstagram\AutomationRule;
use App\Models\SmartInstagram\Post;
use App\Models\SmartInstagram\PostCampaign;
use App\Models\SmartInstagram\PostCampaignKeyword;
use App\Models\SmartInstagram\PostCampaignVersion;
use App\Services\SmartInstagram\OperationLogger;
use App\Services\SmartInstagram\PersianText;
use App\Services\SmartInstagram\WorkspaceContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * ثبت و ویرایش سناریوی هر پست. خروجی نهایی همیشه یک AutomationRule استاندارد است
 * (trigger=comment_keyword، scope_ref=شناسه‌ی پست) تا موتور فعلی کامنت هوشمند بدون تغییر معماری اجرا کند.
 */
class PostCampaignService
{
    public const STATUSES = ['draft' => 'پیش‌نویس', 'test' => 'آزمایشی', 'active' => 'فعال', 'paused' => 'متوقف'];

    public const MATCH_MODES = ['contains' => 'شامل عبارت', 'exact' => 'دقیقاً برابر', 'word' => 'کلمه‌ی کامل', 'pattern' => 'الگو (با *)'];

    public const BUTTON_PRESETS = [
        'product' => ['label' => 'مشاهده محصول', 'type' => 'web_url', 'icon' => 'fa-bag-shopping'],
        'buy' => ['label' => 'خرید', 'type' => 'web_url', 'icon' => 'fa-cart-shopping'],
        'link' => ['label' => 'مشاهده لینک', 'type' => 'web_url', 'icon' => 'fa-link'],
        'site' => ['label' => 'رفتن به سایت', 'type' => 'web_url', 'icon' => 'fa-globe'],
        'info' => ['label' => 'دریافت اطلاعات', 'type' => 'postback', 'icon' => 'fa-circle-info'],
        'quick' => ['label' => 'پاسخ سریع', 'type' => 'postback', 'icon' => 'fa-reply'],
    ];

    /** دکمه‌هایی که API اینستاگرام ارسالشان را پشتیبانی نمی‌کند — در پنل غیرفعال نمایش داده می‌شوند. */
    public const UNAVAILABLE_BUTTONS = ['call' => 'تماس تلفنی', 'share' => 'اشتراک‌گذاری', 'payment' => 'پرداخت درون‌برنامه'];

    public const ATTRIBUTE_NAMES = [
        'keywords' => 'کلمات کلیدی',
        'settings.card.buttons.*.url' => 'لینک دکمه',
        'settings.card.image_url' => 'لینک تصویر کارت',
    ];

    /** قواعد اعتبارسنجی فرم سناریو؛ مشترک بین ویزارد پنل و مینی‌اپ تلگرام. */
    public static function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:190'],
            'intent' => ['nullable', 'in:draft,test,active'],
            'keywords' => ['nullable', 'array', 'max:30'],
            'keywords.*.keyword' => ['nullable', 'string', 'max:120'],
            'keywords.*.match_mode' => ['nullable', 'in:'.implode(',', array_keys(self::MATCH_MODES))],
            'settings' => ['required', 'array'],
            'settings.reply.styles' => ['nullable', 'array', 'max:3'],
            'settings.reply.styles.*' => ['nullable', 'string', 'max:300'],
            'settings.flow.order' => ['nullable', 'in:comment_first,dm_first'],
            'settings.dm.opening_text' => ['nullable', 'string', 'max:900'],
            'settings.dm.opening_button' => ['nullable', 'string', 'max:20'],
            'settings.follow.text' => ['nullable', 'string', 'max:900'],
            'settings.follow.retry_text' => ['nullable', 'string', 'max:900'],
            'settings.follow.button' => ['nullable', 'string', 'max:20'],
            'settings.card.title' => ['nullable', 'string', 'max:80'],
            'settings.card.subtitle' => ['nullable', 'string', 'max:80'],
            'settings.card.intro_text' => ['nullable', 'string', 'max:900'],
            'settings.card.after_text' => ['nullable', 'string', 'max:900'],
            'settings.card.image_url' => ['nullable', 'url', 'max:1000'],
            'settings.card.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'settings.card.buttons' => ['nullable', 'array', 'max:3'],
            'settings.card.buttons.*.label' => ['nullable', 'string', 'max:20'],
            'settings.card.buttons.*.url' => ['nullable', 'url', 'max:1000'],
            'settings.card.buttons.*.reply_text' => ['nullable', 'string', 'max:900'],
            'settings.limits.daily_cap' => ['nullable', 'integer', 'between:0,10000'],
            'settings.limits.reply_delay_seconds' => ['nullable', 'integer', 'between:0,3600'],
            'settings.limits.dm_delay_seconds' => ['nullable', 'integer', 'between:0,3600'],
        ];
    }

    /** خطای منطقی فرم (بعد از قواعد پایه)؛ null یعنی مشکلی نیست. @return array{0:string,1:string}|null */
    public static function logicalError(array $settings, bool $dmEnabled): ?array
    {
        if (!$dmEnabled) {
            return null;
        }
        if (!filled(data_get($settings, 'card.product_id'))) {
            return ['settings.card.product_id', 'برای ارسال دایرکت، انتخاب محصول هدف الزامی است.'];
        }
        $buttons = collect((array) data_get($settings, 'card.buttons', []))->filter(fn ($b) => is_array($b) && trim((string) ($b['label'] ?? '')) !== '');
        $hasLink = $buttons->contains(fn ($b) => ($b['type'] ?? 'web_url') === 'web_url' && (filled($b['url'] ?? null) || filled(data_get($settings, 'card.product_id'))));

        return $hasLink ? null : ['settings.card.buttons', 'کارت دایرکت حداقل یک دکمه‌ی لینک‌دار لازم دارد (یا یک محصول انتخاب کنید).'];
    }

    public function __construct(
        private readonly WorkspaceContext $context,
        private readonly OperationLogger $logger,
    ) {
    }

    public function defaults(): array
    {
        return [
            'reply' => [
                'styles' => [
                    '{name} جان، جزئیات رو توی دایرکت برات فرستادیم 🌿',
                    'ممنون از کامنتت {name}! پیامت رو توی دایرکت ببین ✉️',
                    '{name} عزیز، لینک و اطلاعات کامل توی دایرکت منتظرته 🙌',
                ],
                'ai_personalize' => true,
            ],
            'dm' => [
                'mode' => 'opening_then_card',
                'opening_text' => "سلام {name} جان 👋\nدیدم دنبال اطلاعات این پست بودی؛ برای دریافتش روی دکمه‌ی زیر بزن.",
                'opening_button' => 'ارسال لینک',
            ],
            'flow' => [
                'order' => 'comment_first',
            ],
            'follow' => [
                // این پیام هم به کاربرِ بدون فالو می‌رود و هم به کاربر تازه‌ای که اینستاگرام هنوز وضعیت فالوی او را نمی‌دهد؛
                // پس برای هر دو درست است. فالوورهای شناخته‌شده همان اول کارت می‌گیرند.
                'text' => '{name} جان، لینک آماده‌ست 🎁 اگه هنوز پیج رو فالو نکردی اول فالو کن، بعد روی «فالو کردم» بزن.',
                'retry_text' => '{name} جان، هنوز فالو تأیید نشده؛ اگر انجامش دادی دوباره روی «فالو کردم» بزن.',
                'button' => 'فالو کردم ✅',
                'unknown_policy' => 'send',
                'max_checks' => 3,
            ],
            'card' => [
                'intro_text' => 'بفرما {name} جان، اینم اطلاعاتی که خواستی 👇',
                'image_source' => 'post',
                'image_url' => '',
                'product_id' => null,
                'title' => '',
                'subtitle' => '',
                'buttons' => [['preset' => 'product', 'type' => 'web_url', 'label' => 'مشاهده محصول', 'url' => '', 'reply_text' => '']],
                'after_text' => '',
            ],
            'limits' => [
                'repeat' => 'once',
                'daily_cap' => 500,
                'pause_after_failures' => 5,
                'reply_delay_seconds' => 0,
                'dm_delay_seconds' => 0,
                'stop_on_sensitive' => true,
                'add_tag' => true,
            ],
        ];
    }

    /** کلمات کلیدی صریحی را که مدیر داخل کپشن نشانه‌گذاری کرده استخراج می‌کند. */
    public function keywordsFromCaption(?string $caption): array
    {
        $caption = trim((string) $caption);
        if ($caption === '') {
            return [];
        }

        $found = [];
        $add = function (string $value) use (&$found): void {
            $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
            $value = trim((string) (preg_replace('/^[\s\p{P}\p{S}]+|[\s\p{P}\p{S}]+$/u', '', $value) ?? $value));
            if ($value === '' || mb_strlen($value) > 120 || mb_strlen($value) < 2) {
                return;
            }
            $normalized = PersianText::normalize($value);
            if ($normalized === '' || isset($found[$normalized])) {
                return;
            }
            $found[$normalized] = [
                'keyword' => $value,
                'match_mode' => 'contains',
                'is_active' => true,
            ];
        };

        // اولویت با عبارتی است که مدیر عمداً داخل کوتیشن/گیومه نوشته است.
        preg_match_all('/[«“”"]([^«»“”"]{2,120})[»“”"]/u', $caption, $quoted);
        foreach ((array) ($quoted[1] ?? []) as $value) {
            $add($value);
        }

        // هشتگ‌ها نیز در کپشن معمولاً نقش کلمه‌ی فعال‌کننده را دارند.
        preg_match_all('/(?<![\p{L}\p{N}_])#([\p{L}\p{N}_‌-]{2,120})/u', $caption, $hashtags);
        foreach ((array) ($hashtags[1] ?? []) as $value) {
            $add($value);
        }

        return array_values(array_slice($found, 0, 5));
    }

    /**
     * @param array{title?:string,status?:string,follow_required?:bool,public_reply_enabled?:bool,dm_enabled?:bool,settings?:array,keywords?:array<int,array{keyword:string,match_mode?:string,is_active?:bool}>} $data
     */
    public function save(Post $post, array $data, ?PostCampaign $campaign = null, ?int $adminId = null, ?string $note = null): PostCampaign
    {
        abort_unless((int) $post->workspace_id === $this->context->id(), 404);
        $settings = $this->normalizeSettings((array) ($data['settings'] ?? []));
        $keywords = $this->normalizeKeywords((array) ($data['keywords'] ?? []));
        $status = array_key_exists($data['status'] ?? '', self::STATUSES) ? $data['status'] : 'draft';
        if (in_array($status, ['active', 'test'], true) && !$post->isVerified()) {
            $status = 'draft'; // پست دستی تا تأیید اتصال فعال نمی‌شود
        }

        return DB::transaction(function () use ($post, $data, $campaign, $adminId, $note, $settings, $keywords, $status): PostCampaign {
            $campaign ??= PostCampaign::query()->firstOrNew(['workspace_id' => $post->workspace_id, 'post_id' => $post->id]);
            $isNew = !$campaign->exists;
            $campaign->fill([
                'title' => trim((string) ($data['title'] ?? '')) ?: Str::limit($post->shortCaption(60), 60, ''),
                'status' => $status,
                'follow_required' => (bool) ($data['follow_required'] ?? true),
                'public_reply_enabled' => (bool) ($data['public_reply_enabled'] ?? true),
                'dm_enabled' => (bool) ($data['dm_enabled'] ?? true),
                'settings' => $settings,
                'updated_by' => $adminId,
            ]);
            if ($isNew) {
                $campaign->created_by = $adminId;
                $campaign->version = 1;
            } else {
                $campaign->version++;
            }
            $campaign->save();

            PostCampaignKeyword::query()->where('campaign_id', $campaign->id)->delete();
            foreach ($keywords as $keyword) {
                PostCampaignKeyword::query()->create(['campaign_id' => $campaign->id] + $keyword);
            }

            $rule = $this->compile($campaign->fresh(['keywords', 'post']), $adminId);
            $campaign->forceFill(['automation_rule_id' => $rule->id])->save();

            PostCampaignVersion::query()->create([
                'campaign_id' => $campaign->id,
                'version' => $campaign->version,
                'snapshot' => $this->snapshot($campaign->fresh('keywords')),
                'admin_id' => $adminId,
                'note' => $note,
            ]);

            $this->logger->log($isNew ? 'post_campaign.created' : 'post_campaign.updated', 'سناریوی پست «'.$campaign->title.'» '.($isNew ? 'ثبت' : 'ویرایش').' شد (نسخه '.$campaign->version.' · '.self::STATUSES[$status].').', $campaign, [], 'info', $adminId);

            return $campaign->fresh(['keywords', 'post', 'rule']);
        });
    }

    public function setStatus(PostCampaign $campaign, string $status, ?int $adminId = null): PostCampaign
    {
        abort_unless(array_key_exists($status, self::STATUSES), 422);
        if (in_array($status, ['active', 'test'], true) && !$campaign->post?->isVerified()) {
            abort(back()->with('error', 'این پست هنوز از اتصال واقعی تأیید نشده؛ ابتدا «همگام‌سازی آمار» را بزنید.'));
        }
        if (in_array($status, ['active', 'test'], true) && $campaign->keywords()->where('is_active', true)->doesntExist()) {
            abort(back()->with('error', 'برای فعال‌سازی حداقل یک کلمه‌ی کلیدی فعال لازم است.'));
        }
        $campaign->forceFill(['status' => $status, 'updated_by' => $adminId])->save();
        $campaign->rule?->forceFill(['status' => $status, 'updated_by' => $adminId, 'last_error' => $status === 'active' ? null : $campaign->rule->last_error])->save();
        $this->logger->log('post_campaign.status', 'وضعیت سناریوی «'.$campaign->title.'» → '.self::STATUSES[$status], $campaign, [], 'info', $adminId);

        return $campaign;
    }

    public function delete(PostCampaign $campaign, ?int $adminId = null): void
    {
        DB::transaction(function () use ($campaign): void {
            $rule = $campaign->rule;
            $campaign->delete();
            $rule?->delete();
        });
        $this->logger->log('post_campaign.deleted', 'سناریوی پست «'.$campaign->title.'» حذف شد.', null, [], 'warning', $adminId);
    }

    public function restore(PostCampaign $campaign, PostCampaignVersion $version, ?int $adminId = null): PostCampaign
    {
        $snap = (array) $version->snapshot;

        return $this->save($campaign->post, [
            'title' => $snap['title'] ?? $campaign->title,
            'status' => 'draft',
            'follow_required' => $snap['follow_required'] ?? true,
            'public_reply_enabled' => $snap['public_reply_enabled'] ?? true,
            'dm_enabled' => $snap['dm_enabled'] ?? true,
            'settings' => $snap['settings'] ?? [],
            'keywords' => $snap['keywords'] ?? [],
        ], $campaign, $adminId, 'بازگشت به نسخه‌ی '.$version->version);
    }

    /**
     * قانون‌های قبلی که برای یک پست مشخص ساخته شده‌اند (بدون سناریوی ثبت پست) — برای نمایش و انتقال.
     * @return \Illuminate\Support\Collection<int,AutomationRule>
     */
    public function legacyRules()
    {
        $linked = PostCampaign::query()->where('workspace_id', $this->context->id())->whereNotNull('automation_rule_id')->pluck('automation_rule_id');

        return AutomationRule::query()->where('workspace_id', $this->context->id())
            ->where('trigger', 'comment_keyword')->whereNotNull('scope_ref')
            ->whereNotIn('id', $linked)->orderByDesc('updated_at')->get();
    }

    /** انتقال قانون قبلی به ثبت پست، بدون تغییر رفتار اجرایی تا زمانی که مدیر ذخیره کند. */
    public function importRule(AutomationRule $rule, PostSyncService $sync, ?int $adminId = null): PostCampaign
    {
        abort_unless((int) $rule->workspace_id === $this->context->id() && $rule->scope_ref, 404);
        $post = $sync->registerManual((string) $rule->scope_ref, data_get($rule->conditions, 'post_url'));
        if (!$post->isVerified()) {
            $sync->syncOne($post);
            $post->refresh();
        }

        $settings = $this->defaults();
        $actions = collect((array) $rule->actions);
        $public = $actions->firstWhere('type', 'public_reply');
        if ($public && !empty($public['text'])) {
            $settings['reply']['styles'] = array_values(array_filter((array) ($public['variants'] ?? [$public['text']])));
            $settings['reply']['ai_personalize'] = (bool) ($public['ai_personalize'] ?? false);
        }
        $card = $actions->firstWhere('type', 'product_card');
        if ($card) {
            $settings['dm']['mode'] = 'direct_card';
            $settings['card'] = array_merge($settings['card'], [
                'image_source' => !empty($card['card_image_url']) ? 'url' : (!empty($card['product_id']) ? 'product' : 'post'),
                'image_url' => (string) ($card['card_image_url'] ?? ''),
                'product_id' => $card['product_id'] ?? null,
                'title' => (string) ($card['card_title'] ?? ''),
                'subtitle' => (string) ($card['card_subtitle'] ?? ''),
                'buttons' => [['preset' => 'product', 'type' => 'web_url', 'label' => (string) ($card['card_button_text'] ?? 'مشاهده صفحه'), 'url' => (string) ($card['card_button_url'] ?? ''), 'reply_text' => '']],
            ]);
        }
        $private = $actions->firstWhere('type', 'private_reply');
        if ($private && !$card) {
            $settings['dm']['mode'] = 'direct_card';
            $settings['card']['intro_text'] = (string) ($private['text'] ?? '');
        }

        $campaign = PostCampaign::query()->firstOrNew(['workspace_id' => $post->workspace_id, 'post_id' => $post->id]);
        $campaign->fill([
            'automation_rule_id' => $rule->id,
            'title' => Str::limit(Str::after($rule->name, '— ') ?: $rule->name, 120, ''),
            'status' => $rule->status,
            'follow_required' => false, // رفتار قبلی حفظ می‌شود؛ مدیر می‌تواند روشنش کند
            'public_reply_enabled' => (bool) $public,
            'dm_enabled' => (bool) ($card || $private),
            'settings' => $settings,
            'version' => 1,
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ])->save();

        PostCampaignKeyword::query()->where('campaign_id', $campaign->id)->delete();
        foreach ($this->normalizeKeywords(collect((array) $rule->keywords)->map(fn ($k) => ['keyword' => $k, 'match_mode' => $rule->match_mode])->all()) as $keyword) {
            PostCampaignKeyword::query()->create(['campaign_id' => $campaign->id] + $keyword);
        }
        PostCampaignVersion::query()->firstOrCreate(['campaign_id' => $campaign->id, 'version' => 1], ['snapshot' => $this->snapshot($campaign->fresh('keywords')), 'admin_id' => $adminId, 'note' => 'انتقال از اتومیشن «'.$rule->name.'»']);
        $this->logger->log('post_campaign.imported', 'قانون «'.$rule->name.'» به ثبت پست منتقل شد.', $campaign, [], 'info', $adminId);

        return $campaign;
    }

    /** ساخت/به‌روزرسانی AutomationRule از روی تنظیمات پست. */
    public function compile(PostCampaign $campaign, ?int $adminId = null): AutomationRule
    {
        $settings = (array) $campaign->settings;
        $limits = (array) ($settings['limits'] ?? []);
        $activeKeywords = $campaign->keywords->where('is_active', true);

        $actions = [];
        $publicAction = null;
        if ($campaign->public_reply_enabled) {
            $styles = array_values(array_filter((array) data_get($settings, 'reply.styles', [])));
            $publicAction = array_filter([
                'type' => 'public_reply',
                'text' => $styles[0] ?? '{name} جان، توی دایرکت برات فرستادیم 🌿',
                'variants' => $styles,
                'ai_personalize' => (bool) data_get($settings, 'reply.ai_personalize', false),
                'delay_seconds' => (int) ($limits['reply_delay_seconds'] ?? 0),
            ], fn ($v) => $v !== null && $v !== [] && $v !== 0 && $v !== false);
        }
        $flowAction = $campaign->dm_enabled ? ['type' => 'post_flow', 'campaign_id' => $campaign->id] : null;
        $orderedMessaging = data_get($settings, 'flow.order') === 'dm_first'
            ? [$flowAction, $publicAction]
            : [$publicAction, $flowAction];
        foreach ($orderedMessaging as $action) {
            if ($action !== null) {
                $actions[] = $action;
            }
        }
        if ($limits['add_tag'] ?? true) {
            $actions[] = ['type' => 'add_tag', 'tag' => 'کامنت‌گذار پست'];
        }
        $actions[] = ['type' => 'stop'];

        $repeat = $limits['repeat'] ?? 'once';
        $guards = [
            'max_per_contact_per_day' => $repeat === 'every' ? 0 : 1,
            'cooldown_minutes' => $repeat === 'once' ? 525600 : 0,
            'stop_on_sensitive' => (bool) ($limits['stop_on_sensitive'] ?? true),
            'max_runs_per_day' => max(0, (int) ($limits['daily_cap'] ?? 0)),
            'pause_after_failures' => max(0, (int) ($limits['pause_after_failures'] ?? 0)),
        ];

        $rule = $campaign->rule ?: new AutomationRule(['workspace_id' => $campaign->workspace_id, 'created_by' => $adminId, 'version' => 1]);
        $behaviour = [
            'trigger' => 'comment_keyword',
            'scope_ref' => $campaign->post->media_id,
            'keywords' => $activeKeywords->pluck('keyword')->values()->all(),
            'match_mode' => 'contains',
            'conditions' => array_merge((array) $rule->conditions, [
                'business_hours' => data_get($rule->conditions, 'business_hours', 'any'),
                'keyword_modes' => $activeKeywords->mapWithKeys(fn ($k) => [$k->normalized => $k->match_mode])->all(),
                'post_campaign_id' => $campaign->id,
                'post_url' => $campaign->post->permalink,
            ]),
            'actions' => $actions,
            'guards' => $guards,
        ];
        $changed = !$rule->exists || collect($behaviour)->contains(fn ($value, $key) => json_encode($rule->{$key}) !== json_encode($value));

        $rule->fill($behaviour + [
            'channel_id' => $campaign->post->channel_id,
            'name' => 'ثبت پست — '.Str::limit($campaign->title, 120, ''),
            'status' => $campaign->status,
            'priority' => 20,
            'updated_by' => $adminId,
        ]);
        if ($rule->exists && $changed) {
            $rule->version++;
        }
        $rule->save();

        return $rule;
    }

    public function normalizeSettings(array $input): array
    {
        $defaults = $this->defaults();
        $s = array_replace_recursive($defaults, Arr::only($input, array_keys($defaults)));

        $s['reply']['styles'] = array_values(array_slice(array_filter(array_map(fn ($v) => Str::limit(trim((string) $v), 300, ''), (array) ($input['reply']['styles'] ?? $defaults['reply']['styles']))), 0, 3));
        $s['reply']['ai_personalize'] = filter_var($input['reply']['ai_personalize'] ?? false, FILTER_VALIDATE_BOOL);
        $s['dm']['mode'] = in_array($s['dm']['mode'], ['opening_then_card', 'direct_card'], true) ? $s['dm']['mode'] : 'opening_then_card';
        $s['flow']['order'] = in_array(data_get($s, 'flow.order'), ['comment_first', 'dm_first'], true) ? data_get($s, 'flow.order') : 'comment_first';
        $s['dm']['opening_text'] = Str::limit(trim((string) $s['dm']['opening_text']), 900, '');
        $s['dm']['opening_button'] = Str::limit(trim((string) $s['dm']['opening_button']) ?: 'ارسال لینک', 20, '');
        $s['follow']['button'] = Str::limit(trim((string) $s['follow']['button']) ?: 'فالو کردم ✅', 20, '');
        $s['follow']['unknown_policy'] = in_array($s['follow']['unknown_policy'], ['send', 'ask'], true) ? $s['follow']['unknown_policy'] : 'send';
        $s['follow']['max_checks'] = max(1, min(5, (int) $s['follow']['max_checks']));
        $s['card']['image_source'] = in_array($s['card']['image_source'], ['post', 'product', 'url', 'none'], true) ? $s['card']['image_source'] : 'post';
        $s['card']['title'] = Str::limit(trim((string) $s['card']['title']), 80, '');
        $s['card']['subtitle'] = Str::limit(trim((string) $s['card']['subtitle']), 80, '');
        $s['card']['product_id'] = !empty($s['card']['product_id']) ? (int) $s['card']['product_id'] : null;
        $s['card']['buttons'] = collect((array) ($input['card']['buttons'] ?? $defaults['card']['buttons']))
            ->filter(fn ($b) => is_array($b) && trim((string) ($b['label'] ?? '')) !== '')
            ->take(3)
            ->map(function (array $b): array {
                $type = ($b['type'] ?? 'web_url') === 'postback' ? 'postback' : 'web_url';

                return [
                    'preset' => array_key_exists($b['preset'] ?? '', self::BUTTON_PRESETS) ? $b['preset'] : ($type === 'postback' ? 'quick' : 'link'),
                    'type' => $type,
                    'label' => Str::limit(trim((string) $b['label']), 20, ''),
                    'url' => $type === 'web_url' ? trim((string) ($b['url'] ?? '')) : '',
                    'reply_text' => $type === 'postback' ? Str::limit(trim((string) ($b['reply_text'] ?? '')), 900, '') : '',
                ];
            })->values()->all();
        $s['limits']['repeat'] = in_array($s['limits']['repeat'], ['once', 'daily', 'every'], true) ? $s['limits']['repeat'] : 'once';
        foreach (['daily_cap' => [0, 10000], 'pause_after_failures' => [0, 50], 'reply_delay_seconds' => [0, 3600], 'dm_delay_seconds' => [0, 3600]] as $key => [$min, $max]) {
            $s['limits'][$key] = max($min, min($max, (int) $s['limits'][$key]));
        }
        foreach (['stop_on_sensitive', 'add_tag'] as $key) {
            $s['limits'][$key] = filter_var($input['limits'][$key] ?? $defaults['limits'][$key], FILTER_VALIDATE_BOOL);
        }

        return $s;
    }

    /** @return array<int,array{keyword:string,normalized:string,match_mode:string,is_active:bool}> */
    public function normalizeKeywords(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $row = is_array($row) ? $row : ['keyword' => (string) $row];
            $keyword = trim(preg_replace('/\s+/u', ' ', str_replace("\u{200C}", ' ', (string) ($row['keyword'] ?? ''))) ?? '');
            $normalized = PersianText::normalize($keyword);
            if ($normalized === '' || isset($out[$normalized])) {
                continue;
            }
            $out[$normalized] = [
                'keyword' => Str::limit($keyword, 120, ''),
                'normalized' => Str::limit($normalized, 120, ''),
                'match_mode' => array_key_exists($row['match_mode'] ?? '', self::MATCH_MODES) ? $row['match_mode'] : 'contains',
                'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOL),
            ];
        }

        return array_values(array_slice($out, 0, 30));
    }

    private function snapshot(PostCampaign $campaign): array
    {
        return [
            'title' => $campaign->title,
            'status' => $campaign->status,
            'follow_required' => $campaign->follow_required,
            'public_reply_enabled' => $campaign->public_reply_enabled,
            'dm_enabled' => $campaign->dm_enabled,
            'settings' => $campaign->settings,
            'keywords' => $campaign->keywords->map(fn ($k) => ['keyword' => $k->keyword, 'match_mode' => $k->match_mode, 'is_active' => $k->is_active])->values()->all(),
        ];
    }
}
