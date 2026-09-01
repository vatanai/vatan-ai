<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TelegramCampaign;
use App\Models\TelegramUser;
use App\Services\TelegramIdentityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class TelegramApiController extends Controller
{
    public function __construct(private readonly TelegramIdentityService $identity)
    {
    }

    public function users(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);
        $users = $this->userQuery($request)->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => $users->getCollection()->map(fn (TelegramUser $user): array => $this->userPayload($user))->values(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function storeUser(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telegram_id' => ['required', 'integer', 'min:1'],
            'username' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'language_code' => ['nullable', 'string', 'max:12'],
            'is_premium' => ['nullable', 'boolean'],
            'chat_id' => ['nullable', 'string', 'max:100'],
        ]);

        $wasExisting = TelegramUser::query()->where('telegram_id', $data['telegram_id'])->exists();
        $user = $this->identity->upsert(
            Arr::only($data, ['telegram_id', 'username', 'first_name', 'last_name', 'language_code', 'is_premium']) + ['id' => $data['telegram_id']],
            null,
            null,
            [],
            $data['chat_id'] ?? (string) $data['telegram_id'],
        );

        return response()->json([
            'data' => $this->userPayload($user->loadMissing('user')->loadCount('productClicks')),
            'created' => ! $wasExisting,
        ], $wasExisting ? 200 : 201);
    }

    public function campaigns(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);
        $campaigns = TelegramCampaign::query()
            ->with('admin:id,name')
            ->withCount('logs')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'data' => $campaigns->getCollection()->map(fn (TelegramCampaign $campaign): array => $this->campaignPayload($campaign))->values(),
            'meta' => [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
            ],
        ]);
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'segment_definition' => ['required', 'array'],
            'body' => ['nullable', 'string', 'max:10000'],
            'media_type' => ['nullable', Rule::in(['photo', 'video', 'animation', 'document'])],
            'media_file_id' => ['nullable', 'string', 'max:2048'],
            'buttons' => ['nullable', 'array'],
            'buttons.*' => ['array'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $campaign = TelegramCampaign::query()->create([
            'created_by' => $request->user('admin')->id,
            'name' => $data['name'],
            'segment_definition' => $data['segment_definition'],
            'status' => 'draft',
            'body' => $data['body'] ?? null,
            'media_type' => $data['media_type'] ?? null,
            'media_file_id' => $data['media_file_id'] ?? null,
            'buttons' => $data['buttons'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
        ]);

        return response()->json(['data' => $this->campaignPayload($campaign->load('admin:id,name')), 'message' => 'پیش‌نویس کمپین ذخیره شد.'], 201);
    }

    private function userQuery(Request $request)
    {
        $term = trim((string) $request->input('q', ''));

        return TelegramUser::query()
            ->with('user:id,name,last_name,tokens')
            ->withCount('productClicks')
            ->when($term !== '', function ($query) use ($term): void {
                $like = '%' . $term . '%';
                $query->where(function ($nested) use ($term, $like): void {
                    if (ctype_digit($term)) {
                        $nested->where('telegram_id', (int) $term);
                    }
                    $nested->orWhere('username', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like);
                });
            })
            ->when($request->input('status') === 'blocked', fn ($query) => $query->where('is_blocked', true))
            ->when($request->input('status') === 'active', fn ($query) => $query->where('is_blocked', false))
            ->when($request->input('linked') === 'yes', fn ($query) => $query->whereNotNull('user_id'))
            ->when($request->input('linked') === 'no', fn ($query) => $query->whereNull('user_id'))
            ->when($request->filled('source'), fn ($query) => $query->whereHas('productClicks', fn ($clicks) => $clicks->where('source', $request->input('source'))))
            ->when($request->filled('product_id'), fn ($query) => $query->whereHas('productClicks', fn ($clicks) => $clicks->where('product_id', (int) $request->input('product_id'))))
            ->when($request->filled('active_days'), fn ($query) => $query->where('last_active_at', '>=', now()->subDays(max(1, (int) $request->input('active_days')))))
            ->when($request->filled('created_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->input('created_from')))
            ->when($request->filled('created_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->input('created_to')))
            ->latest('last_active_at')
            ->latest('id');
    }

    private function userPayload(TelegramUser $user): array
    {
        return [
            'id' => $user->id,
            'telegram_id' => $user->telegram_id,
            'username' => $user->username,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'language_code' => $user->language_code,
            'is_premium' => (bool) $user->is_premium,
            'is_blocked' => (bool) $user->is_blocked,
            'started_at' => $user->started_at?->toISOString(),
            'last_active_at' => $user->last_active_at?->toISOString(),
            'registration_completed_at' => $user->registration_completed_at?->toISOString(),
            'registration_state' => $user->registration_state,
            'user_id' => $user->user_id,
            'site_user' => $user->relationLoaded('user') && $user->user ? [
                'id' => $user->user->id,
                'name' => trim($user->user->name . ' ' . $user->user->last_name),
                'tokens' => (int) $user->user->tokens,
            ] : null,
            'product_clicks_count' => (int) ($user->product_clicks_count ?? 0),
            'created_at' => $user->created_at?->toISOString(),
        ];
    }

    private function campaignPayload(TelegramCampaign $campaign): array
    {
        return [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'segment_definition' => $campaign->segment_definition ?: [],
            'status' => $campaign->status,
            'body' => $campaign->body,
            'media_type' => $campaign->media_type,
            'media_file_id' => $campaign->media_file_id,
            'buttons' => $campaign->buttons,
            'scheduled_at' => $campaign->scheduled_at?->toISOString(),
            'recipient_count' => (int) $campaign->recipient_count,
            'sent_count' => (int) $campaign->sent_count,
            'failed_count' => (int) $campaign->failed_count,
            'logs_count' => (int) ($campaign->logs_count ?? 0),
            'created_by' => $campaign->created_by,
            'created_at' => $campaign->created_at?->toISOString(),
            'admin' => $campaign->relationLoaded('admin') && $campaign->admin ? [
                'id' => $campaign->admin->id,
                'name' => $campaign->admin->name,
            ] : null,
        ];
    }
}
