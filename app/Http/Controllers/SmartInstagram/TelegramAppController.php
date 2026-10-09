<?php

namespace App\Http\Controllers\SmartInstagram;

use App\Http\Controllers\Controller;
use App\Models\SmartInstagram\TelegramAdmin;
use App\Models\Product;
use App\Models\SmartInstagram\Post;
use App\Models\SmartInstagram\PostCampaign;
use App\Services\SmartInstagram\Gateways\GatewayManager;
use App\Services\SmartInstagram\Gateways\RichInstagramGateway;
use App\Services\SmartInstagram\PersianText;
use App\Services\SmartInstagram\Posts\PostCampaignService;
use App\Services\SmartInstagram\Posts\PostContentWriter;
use App\Services\SmartInstagram\Posts\PostSyncService;
use App\Services\SmartInstagram\Telegram\InstagramTelegramBot;
use App\Services\SmartInstagram\WorkspaceContext;
use App\Services\TelegramInitDataValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * مینی‌اپ تلگرام «ثبت پست»: همان سناریوی ویزارد پنل (PostCampaignService) با رابط موبایلی ساده.
 * احراز هویت بدون کوکی: هر درخواست هدر X-Telegram-Init-Data را می‌فرستد و با توکن بات اینستاگرام
 * اعتبارسنجی می‌شود؛ حساب تلگرام باید از پنل به یک ادمین وصل شده باشد.
 */
class TelegramAppController extends Controller
{
    public function __construct(
        private readonly TelegramInitDataValidator $validator,
        private readonly InstagramTelegramBot $bot,
        private readonly WorkspaceContext $context,
    ) {
    }

    public function show(Request $request): View
    {
        return view('telegram.instagram-app', [
            'postId' => $request->integer('post') ?: null,
            'botUsername' => $this->bot->username(),
        ]);
    }

    public function bootstrap(Request $request, PostSyncService $sync): JsonResponse
    {
        $member = $this->admin($request, 'view');
        $channel = $sync->channel();
        $gateway = null;
        try {
            $gateway = $channel ? app(GatewayManager::class)->for($channel) : null;
        } catch (\Throwable) {
        }

        $products = Product::query()->where('status', 'active')->orderByDesc('created_at')->orderByDesc('id')->limit(500)
            ->get(['id', 'name_fa', 'slug', 'product_code', 'cover', 'thumbnail', 'sample_outputs'])
            ->map(fn (Product $p) => ['id' => $p->id, 'name' => $p->name_fa, 'image' => $p->displayImageUrl(), 'url' => route('app.product', $p->route_slug)])
            ->values();

        return response()->json([
            'ok' => true,
            'admin' => ['name' => $member->displayName()],
            'can_manage' => $member->allows('manage_automation'),
            'channel' => $channel ? ['name' => $channel->name, 'username' => $channel->username, 'status' => $channel->status] : null,
            'flags' => [
                'ai' => (bool) config('smart_instagram.ai.enabled') && filled(config('services.openrouter.api_key')),
                'follow_supported' => $gateway instanceof RichInstagramGateway && $channel?->gateway !== 'sandbox',
                'outbound' => (bool) config('smart_instagram.outbound_enabled') && (bool) $channel?->outbound_enabled,
            ],
            'statuses' => PostCampaignService::STATUSES,
            'match_modes' => PostCampaignService::MATCH_MODES,
            'presets' => PostCampaignService::BUTTON_PRESETS,
            'products' => $products,
            'posts' => $this->posts(),
        ]);
    }

    public function posts(): array
    {
        return Post::query()->where('workspace_id', $this->context->id())
            ->with('campaign:id,post_id,status,version,updated_at')
            ->orderByDesc('published_at')->orderByDesc('id')->limit(60)->get()
            ->map(fn (Post $post) => $this->postSummary($post))->all();
    }

    public function list(Request $request): JsonResponse
    {
        $this->admin($request, 'view');

        return response()->json(['ok' => true, 'posts' => $this->posts()]);
    }

    public function sync(Request $request, PostSyncService $sync): JsonResponse
    {
        $this->admin($request, 'manage_automation');
        $result = $sync->syncRecent(30);

        return response()->json(['ok' => $result['ok'], 'message' => $result['message'], 'posts' => $this->posts()], $result['ok'] ? 200 : 422);
    }

    public function showPost(Request $request, Post $post, PostCampaignService $campaigns): JsonResponse
    {
        $this->admin($request, 'view');
        $this->own($post);
        $post->load('campaign.keywords');
        $campaign = $post->campaign;

        if ($campaign) {
            $data = [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'status' => $campaign->status,
                'version' => $campaign->version,
                'public_reply_enabled' => (bool) $campaign->public_reply_enabled,
                'dm_enabled' => (bool) $campaign->dm_enabled,
                'follow_required' => (bool) $campaign->follow_required,
                'settings' => $campaigns->normalizeSettings((array) $campaign->settings),
                'keywords' => $campaign->keywords->map->only(['keyword', 'match_mode', 'is_active'])->values(),
                'stats' => $this->stats($campaign),
            ];
        } else {
            $keywords = $campaigns->keywordsFromCaption($post->caption) ?: [['keyword' => 'لینک', 'match_mode' => 'contains', 'is_active' => true]];
            $data = [
                'id' => null, 'title' => '', 'status' => 'draft', 'version' => 0,
                'public_reply_enabled' => true, 'dm_enabled' => true, 'follow_required' => true,
                'settings' => $campaigns->defaults(),
                'keywords' => $keywords,
                'stats' => null,
            ];
            $data['settings']['card']['product_id'] = $this->suggestProduct($post);
        }

        return response()->json([
            'ok' => true,
            'post' => $this->postSummary($post) + ['caption' => (string) $post->caption],
            'campaign' => $data,
        ]);
    }

    public function save(Request $request, Post $post, PostCampaignService $campaigns): JsonResponse
    {
        $admin = $this->admin($request, 'manage_automation');
        $this->own($post);

        $validator = Validator::make($request->all(), PostCampaignService::rules(), [], PostCampaignService::ATTRIBUTE_NAMES);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }
        $settings = (array) $request->input('settings');
        $dmEnabled = $request->boolean('dm_enabled');
        if ($error = PostCampaignService::logicalError($settings, $dmEnabled)) {
            return $this->fail($error[1], $error[0]);
        }
        $keywords = collect((array) $request->input('keywords'))
            ->filter(fn ($k) => is_array($k) && PersianText::normalize((string) ($k['keyword'] ?? '')) !== '')->values();
        if ($keywords->isEmpty()) {
            $keywords = collect($campaigns->keywordsFromCaption($post->caption));
        }
        if ($keywords->isEmpty()) {
            return $this->fail('حداقل یک کلمه‌ی کلیدی لازم است.', 'keywords');
        }
        $intent = (string) $request->input('intent', 'draft');

        $campaign = $campaigns->save($post, [
            'title' => $request->input('title'),
            'status' => $intent,
            'follow_required' => $request->boolean('follow_required'),
            'public_reply_enabled' => $request->boolean('public_reply_enabled'),
            'dm_enabled' => $dmEnabled,
            'settings' => $settings,
            'keywords' => $keywords->all(),
        ], $post->campaign, $admin->admin_id, 'از مینی‌اپ تلگرام — '.$admin->displayName());

        $message = match ($campaign->status) {
            'active' => 'فعال شد 🚀 از همین حالا روی کامنت‌ها اجرا می‌شود.',
            'test' => 'در حالت آزمایشی ذخیره شد.',
            default => 'پیش‌نویس ذخیره شد.',
        };
        if ($intent !== $campaign->status && !$post->isVerified()) {
            $message = 'ذخیره شد، ولی پست هنوز از اتصال اینستاگرام تأیید نشده؛ فعلاً پیش‌نویس می‌ماند.';
        }

        return response()->json(['ok' => true, 'message' => $message, 'campaign' => ['id' => $campaign->id, 'status' => $campaign->status, 'version' => $campaign->version]]);
    }

    public function status(Request $request, Post $post, PostCampaignService $campaigns): JsonResponse
    {
        $admin = $this->admin($request, 'manage_automation');
        $this->own($post);
        $status = (string) $request->input('status');
        $campaign = $post->campaign?->load('post', 'rule', 'keywords');
        if (!$campaign || !array_key_exists($status, PostCampaignService::STATUSES)) {
            return $this->fail('سناریویی برای این پست پیدا نشد.');
        }
        if (in_array($status, ['active', 'test'], true)) {
            if (!$post->isVerified()) {
                return $this->fail('این پست هنوز از اتصال اینستاگرام تأیید نشده است.');
            }
            if ($campaign->keywords->where('is_active', true)->isEmpty()) {
                return $this->fail('برای فعال‌سازی حداقل یک کلمه‌ی کلیدی فعال لازم است.');
            }
        }
        $campaigns->setStatus($campaign, $status, $admin->admin_id);

        return response()->json(['ok' => true, 'status' => $status, 'message' => 'وضعیت: '.PostCampaignService::STATUSES[$status]]);
    }

    public function ai(Request $request, PostContentWriter $writer): JsonResponse
    {
        $this->admin($request, 'manage_automation');
        $data = $request->validate([
            'post_id' => ['nullable', 'integer'],
            'product_id' => ['nullable', 'integer'],
            'keywords' => ['nullable', 'array', 'max:30'],
            'keywords.*' => ['nullable', 'string', 'max:120'],
            'hint' => ['nullable', 'string', 'max:500'],
            'sections' => ['nullable', 'array'],
            'sections.*' => ['string', 'in:public_reply,follow,card'],
        ]);
        if (!config('smart_instagram.ai.enabled')) {
            return $this->fail('هوش مصنوعی در تنظیمات سرور خاموش است.');
        }
        $post = !empty($data['post_id']) ? Post::query()->where('workspace_id', $this->context->id())->find((int) $data['post_id']) : null;
        $result = $writer->generate($data['sections'] ?? ['public_reply', 'follow', 'card'], $post, $data);

        return response()->json($result, ($result['ok'] ?? false) ? 200 : 422);
    }

    private function postSummary(Post $post): array
    {
        $campaign = $post->campaign;

        return [
            'id' => $post->id,
            'cover' => $post->coverUrl(),
            'short' => $post->shortCaption(110),
            'kind' => $post->kindLabel(),
            'permalink' => $post->permalink,
            'published_at' => $post->published_at?->toIso8601String(),
            'verified' => $post->isVerified(),
            'likes' => $post->like_count,
            'comments' => $post->comments_count,
            'campaign' => $campaign ? [
                'id' => $campaign->id,
                'status' => $campaign->status,
                'status_label' => PostCampaignService::STATUSES[$campaign->status] ?? $campaign->status,
            ] : null,
        ];
    }

    private function stats(PostCampaign $campaign): ?array
    {
        if (!$campaign->automation_rule_id) {
            return null;
        }
        $runs = \App\Models\SmartInstagram\AutomationRun::query()->where('rule_id', $campaign->automation_rule_id);

        return [
            'matched' => (clone $runs)->count(),
            'succeeded' => (clone $runs)->whereIn('status', ['success', 'partial'])->count(),
            'failed' => (clone $runs)->where('status', 'failed')->count(),
        ];
    }

    private function suggestProduct(Post $post): ?int
    {
        $caption = PersianText::normalize((string) $post->caption);
        if ($caption === '') {
            return null;
        }
        $terms = collect(preg_split('/\s+/u', $caption, -1, PREG_SPLIT_NO_EMPTY))->filter(fn ($t) => mb_strlen($t) >= 3)->unique()->take(40);
        $best = null;
        $bestScore = 0;
        foreach (Product::query()->where('status', 'active')->latest('id')->limit(200)->get(['id', 'name_fa', 'description_fa']) as $product) {
            $haystack = PersianText::normalize($product->name_fa.' '.strip_tags((string) $product->description_fa));
            $score = $terms->sum(fn ($t) => str_contains($haystack, $t) ? 1 : 0);
            if ($score > $bestScore) {
                [$best, $bestScore] = [$product->id, $score];
            }
        }

        return $bestScore >= 2 ? $best : null;
    }

    /** ادمین متصل به حساب تلگرام درخواست؛ در غیر این صورت ۴۰۱/۴۰۳ JSON. */
    private function admin(Request $request, string $ability): TelegramAdmin
    {
        $initData = (string) $request->header('X-Telegram-Init-Data', '');
        try {
            $validated = $this->validator->validate($initData, $this->bot->token() ?: '-');
        } catch (ValidationException $e) {
            abort(response()->json(['ok' => false, 'code' => 'TELEGRAM_AUTH_FAILED', 'message' => collect($e->errors())->flatten()->first() ?: 'ورود تلگرام تأیید نشد.'], 401));
        }
        $account = $this->bot->account((int) $validated['user']['id']);
        if (!$account) {
            abort(response()->json([
                'ok' => false, 'code' => 'TELEGRAM_NOT_LINKED',
                'telegram_id' => (int) $validated['user']['id'],
                'message' => 'این حساب تلگرام هنوز به پنل وصل نشده است.',
            ], 403));
        }
        if (!$account->allows($ability)) {
            abort(response()->json(['ok' => false, 'code' => 'FORBIDDEN', 'message' => 'دسترسی این بخش برای نقش شما فعال نیست.'], 403));
        }
        if (!$account->last_seen_at || $account->last_seen_at->lt(now()->subMinutes(5))) {
            $account->forceFill(['last_seen_at' => now()])->save();
        }
        if ($account->admin) {
            Auth::guard('admin')->setUser($account->admin);
        }

        return $account;
    }

    private function own(Post $post): void
    {
        abort_unless((int) $post->workspace_id === $this->context->id(), 404);
    }

    private function fail(string $message, ?string $field = null, int $status = 422): JsonResponse
    {
        return response()->json(['ok' => false, 'message' => $message, 'field' => $field], $status);
    }
}
