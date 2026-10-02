<?php

namespace App\Services\SmartInstagram\Ai;

use App\Models\SmartInstagram\AiProfile;
use App\Services\SmartInstagram\OperationLogger;
use App\Services\SmartInstagram\WorkspaceContext;
use Illuminate\Support\Facades\DB;

/** پروفایل گفتمان (پرامپت، لحن، قواعد) — نسخه‌دار؛ هر ذخیره نسخه‌ی تازه می‌سازد و برگشت‌پذیر است. */
class AiProfileService
{
    public function __construct(
        private readonly WorkspaceContext $context,
        private readonly OperationLogger $logger,
    ) {
    }

    public function active(): AiProfile
    {
        return AiProfile::query()->where('workspace_id', $this->context->id())->where('is_active', true)->latest('version')->first()
            ?? AiProfile::query()->create([
                'workspace_id' => $this->context->id(),
                'version' => 1,
                'is_active' => true,
                'assistant_name' => 'دستیار فروش',
                'persona_prompt' => 'تو دستیار فروش هستی. کوتاه و محترمانه پاسخ بده و فقط از دانش تأییدشده استفاده کن.',
                'escalation_keywords' => ['شکایت', 'بازگشت وجه'],
                'forbidden_phrases' => [],
            ]);
    }

    public function saveNewVersion(array $data, ?int $adminId = null, bool $activate = true): AiProfile
    {
        return DB::transaction(function () use ($data, $adminId, $activate): AiProfile {
            $next = (int) AiProfile::query()->where('workspace_id', $this->context->id())->lockForUpdate()->max('version') + 1;
            if ($activate) {
                AiProfile::query()->where('workspace_id', $this->context->id())->update(['is_active' => false]);
            }

            $profile = AiProfile::query()->create([
                'workspace_id' => $this->context->id(),
                'version' => $next,
                'is_active' => $activate,
                'assistant_name' => $data['assistant_name'] ?? null,
                'persona_prompt' => $data['persona_prompt'],
                'tone' => $data['tone'] ?? 'friendly',
                'reply_length' => $data['reply_length'] ?? 'short',
                'bot_disclosure' => $data['bot_disclosure'] ?? 'when_asked',
                'forbidden_phrases' => $this->lines($data['forbidden_phrases'] ?? []),
                'escalation_keywords' => $this->lines($data['escalation_keywords'] ?? []),
                'min_confidence' => (float) ($data['min_confidence'] ?? 0.65),
                'model' => ($data['model'] ?? null) ?: null,
                'reply_mode' => 'suggest',
                'change_note' => $data['change_note'] ?? null,
                'created_by' => $adminId,
            ]);
            $this->logger->log('ai_profile.saved', 'نسخه‌ی '.$next.' پروفایل گفتمان ذخیره شد.', $profile, [], 'info', $adminId);

            return $profile;
        });
    }

    public function activate(AiProfile $profile, ?int $adminId = null): void
    {
        DB::transaction(function () use ($profile): void {
            AiProfile::query()->where('workspace_id', $profile->workspace_id)->update(['is_active' => false]);
            $profile->forceFill(['is_active' => true])->save();
        });
        $this->logger->log('ai_profile.activated', 'نسخه‌ی '.$profile->version.' پروفایل گفتمان فعال شد.', $profile, [], 'info', $adminId);
    }

    /** @return array<int,string> */
    public function lines(string|array|null $value): array
    {
        $items = is_array($value) ? $value : preg_split('/[\n،,]+/u', (string) $value);

        return array_values(array_unique(array_filter(array_map(fn ($v) => trim((string) $v), $items ?: []))));
    }
}
