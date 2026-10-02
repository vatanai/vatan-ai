<?php

namespace App\Http\Controllers\Admin\SmartInstagram;

use App\Models\SmartInstagram\AiProfile;
use App\Models\SmartInstagram\AiRun;
use App\Models\SmartInstagram\AiSuggestion;
use App\Models\SmartInstagram\KnowledgeSource;
use App\Services\SmartInstagram\Ai\AiProfileService;
use App\Services\SmartInstagram\Ai\KnowledgeExtractor;
use App\Services\SmartInstagram\Ai\KnowledgeService;
use App\Services\SmartInstagram\Ai\SalesAssistant;
use App\Services\SmartInstagram\MetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * دانش هوش مصنوعی (پروپوزال ۵.۶ و ۹): سبک گفتمان (پرامپت نسخه‌دار + بارگذاری فایل پرامپت)،
 * منابع دانش (متن/فایل، تأیید، اعتبار، تحلیل)، آزمایشگاه دستیار و چرخه‌ی یادگیری.
 */
class KnowledgeController extends Controller
{
    public const TABS = ['profile' => 'سبک گفتمان و پرامپت', 'sources' => 'منابع دانش', 'playground' => 'آزمایش دستیار', 'learning' => 'یادگیری و بازخورد'];

    public function index(Request $request, AiProfileService $profiles, MetricsService $metrics): View
    {
        $this->authorizeAbility('view');
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? (string) $request->query('tab') : 'profile';
        $category = (string) $request->query('category', '');
        $status = (string) $request->query('status', '');

        $data = [
            'tab' => $tab,
            'tabs' => self::TABS,
            'profile' => $profiles->active(),
            'categories' => config('smart_instagram.knowledge.categories'),
            'canManage' => $this->context->can($this->admin(), 'manage_knowledge'),
            'aiEnabled' => (bool) config('smart_instagram.ai.enabled'),
            'stats' => [
                'sources' => KnowledgeSource::query()->where('workspace_id', $this->ws())->count(),
                'approved' => KnowledgeSource::query()->where('workspace_id', $this->ws())->usableByAi()->count(),
                'drafts' => KnowledgeSource::query()->where('workspace_id', $this->ws())->where('status', 'draft')->count(),
                'chunks' => (int) KnowledgeSource::query()->where('workspace_id', $this->ws())->sum('chunk_count'),
            ],
        ];

        if ($tab === 'profile') {
            $data['versions'] = AiProfile::query()->where('workspace_id', $this->ws())->with('creator:id,name')->latest('version')->limit(15)->get();
        }
        if ($tab === 'sources') {
            $data['sources'] = KnowledgeSource::query()->where('workspace_id', $this->ws())
                ->when($category !== '', fn ($q) => $q->where('category', $category))
                ->when($status !== '', fn ($q) => $status === 'usable' ? $q->usableByAi() : $q->where('status', $status))
                ->with('approver:id,name')
                ->latest('updated_at')
                ->paginate(20, ['id', 'title', 'category', 'source_type', 'original_filename', 'status', 'ai_allowed', 'version', 'valid_until', 'chunk_count', 'char_count', 'digest_status', 'usage_count', 'approved_by', 'approved_at', 'updated_at'])
                ->withQueryString();
            $data['category'] = $category;
            $data['status'] = $status;
        }
        if ($tab === 'learning') {
            $from = now()->subDays(30);
            $data['gaps'] = $metrics->knowledgeGaps($this->ws(), $from, 15);
            $data['feedback'] = AiSuggestion::query()->where('workspace_id', $this->ws())->whereIn('status', ['edited', 'rejected'])
                ->with(['reviewer:id,name', 'conversation.contact:id,username,display_name'])->latest('reviewed_at')->limit(20)->get();
            $data['quality'] = AiSuggestion::query()->where('workspace_id', $this->ws())->where('created_at', '>=', $from)
                ->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');
            $data['runs'] = AiRun::query()->where('workspace_id', $this->ws())->where('created_at', '>=', $from)
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed, AVG(confidence) as confidence, SUM(COALESCE(prompt_tokens,0) + COALESCE(completion_tokens,0)) as tokens, SUM(cost_usd) as cost, AVG(duration_ms) as duration")
                ->first();
            $data['pendingLearned'] = KnowledgeSource::query()->where('workspace_id', $this->ws())->where('category', 'approved_reply')->where('status', 'draft')->latest()->limit(10)->get(['id', 'title', 'created_at']);
        }

        return view('admin.smart-instagram.knowledge.index', $data);
    }

    public function saveProfile(Request $request, AiProfileService $profiles, KnowledgeExtractor $extractor): RedirectResponse
    {
        $this->authorizeAbility('manage_knowledge');
        $data = $request->validate([
            'assistant_name' => ['nullable', 'string', 'max:80'],
            'persona_prompt' => ['nullable', 'string', 'max:12000'],
            'prompt_file' => ['nullable', 'file', 'max:1024', 'extensions:txt,md,docx,json,html,htm'],
            'prompt_file_mode' => ['nullable', 'in:replace,append'],
            'tone' => ['required', 'in:friendly,formal,energetic,luxury,calm'],
            'reply_length' => ['required', 'in:short,medium,long'],
            'bot_disclosure' => ['required', 'in:always,when_asked,never_claim_human'],
            'forbidden_phrases' => ['nullable', 'string', 'max:3000'],
            'escalation_keywords' => ['nullable', 'string', 'max:3000'],
            'min_confidence' => ['required', 'numeric', 'between:0.3,0.95'],
            'model' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9._\-]+\/[a-z0-9._:\-]+$/i'],
            'change_note' => ['nullable', 'string', 'max:300'],
        ]);

        $prompt = trim((string) ($data['persona_prompt'] ?? ''));
        if ($request->hasFile('prompt_file')) {
            try {
                $filePrompt = $extractor->fromUpload($request->file('prompt_file'));
            } catch (\Throwable $e) {
                return back()->withInput()->withErrors(['prompt_file' => $e->getMessage()]);
            }
            $prompt = ($data['prompt_file_mode'] ?? 'replace') === 'append' && $prompt !== '' ? $prompt."\n\n".$filePrompt : $filePrompt;
        }
        if (mb_strlen($prompt) < 20) {
            return back()->withInput()->withErrors(['persona_prompt' => 'پرامپت سبک گفتمان حداقل ۲۰ نویسه لازم دارد.']);
        }
        $data['persona_prompt'] = mb_substr($prompt, 0, 12000);

        $profile = $profiles->saveNewVersion($data, $this->admin()?->id);

        return redirect()->route('admin.smart-instagram.knowledge.index', ['tab' => 'profile'])->with('success', 'نسخه‌ی '.$profile->version.' سبک گفتمان ذخیره و فعال شد.');
    }

    public function activateProfile(AiProfile $profile, AiProfileService $profiles): RedirectResponse
    {
        $this->authorizeAbility('manage_knowledge');
        abort_unless((int) $profile->workspace_id === $this->ws(), 404);
        $profiles->activate($profile, $this->admin()?->id);

        return back()->with('success', 'نسخه‌ی '.$profile->version.' فعال شد.');
    }

    public function storeSource(Request $request, KnowledgeService $knowledge): RedirectResponse
    {
        $this->authorizeAbility('manage_knowledge');
        $extensions = implode(',', config('smart_instagram.knowledge.extensions'));
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'category' => ['required', 'in:'.implode(',', array_keys(config('smart_instagram.knowledge.categories')))],
            'content' => ['nullable', 'string', 'max:200000', 'required_without:file'],
            'file' => ['nullable', 'file', 'max:'.(int) config('smart_instagram.knowledge.max_upload_kb', 4096), 'extensions:'.$extensions, 'required_without:content'],
            'ai_allowed' => ['nullable', 'boolean'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],
            'approve_now' => ['nullable', 'boolean'],
            'digest_now' => ['nullable', 'boolean'],
        ]);

        try {
            $source = $knowledge->create([
                'title' => $data['title'],
                'category' => $data['category'],
                'content' => $data['content'] ?? '',
                'ai_allowed' => $request->boolean('ai_allowed', true),
                'valid_until' => $data['valid_until'] ?? null,
                'status' => $request->boolean('approve_now') ? 'approved' : 'draft',
            ], $request->file('file'), $this->admin()?->id);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['file' => $e->getMessage()]);
        }

        if ($request->boolean('digest_now') && config('smart_instagram.ai.enabled')) {
            $knowledge->queueDigest($source);
        }

        return redirect()->route('admin.smart-instagram.knowledge.sources.show', $source)->with('success', 'منبع دانش ذخیره و برای جست‌وجو تکه‌بندی شد ('.$source->chunk_count.' بخش).');
    }

    public function showSource(KnowledgeSource $source): View
    {
        $this->authorizeAbility('view');
        $this->own($source);

        return view('admin.smart-instagram.knowledge.source', [
            'source' => $source->load(['approver:id,name']),
            'chunks' => $source->chunks()->orderBy('position')->limit(40)->get(['id', 'position', 'content']),
            'categories' => config('smart_instagram.knowledge.categories'),
            'canManage' => $this->context->can($this->admin(), 'manage_knowledge'),
        ]);
    }

    public function updateSource(Request $request, KnowledgeSource $source, KnowledgeService $knowledge): RedirectResponse
    {
        $this->authorizeAbility('manage_knowledge');
        $this->own($source);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'category' => ['required', 'in:'.implode(',', array_keys(config('smart_instagram.knowledge.categories')))],
            'content' => ['required', 'string', 'max:200000'],
            'ai_allowed' => ['nullable', 'boolean'],
            'valid_until' => ['nullable', 'date'],
        ]);
        $data['ai_allowed'] = $request->boolean('ai_allowed');
        $knowledge->update($source, $data, $this->admin()?->id);

        return back()->with('success', $source->status === 'draft' ? 'ذخیره شد؛ چون متن تغییر کرد، تا تأیید دوباره در پاسخ‌ها استفاده نمی‌شود.' : 'ذخیره شد.');
    }

    public function sourceAction(Request $request, KnowledgeSource $source, KnowledgeService $knowledge): RedirectResponse
    {
        $this->authorizeAbility('manage_knowledge');
        $this->own($source);
        $data = $request->validate(['action' => ['required', 'in:approve,archive,draft,digest']]);

        if ($data['action'] === 'digest') {
            abort_unless(config('smart_instagram.ai.enabled'), 422, 'هوش مصنوعی خاموش است.');
            $knowledge->queueDigest($source);

            return back()->with('success', 'تحلیل هوشمند این منبع در صف قرار گرفت؛ چند ثانیه‌ی دیگر صفحه را تازه کنید.');
        }

        $status = ['approve' => 'approved', 'archive' => 'archived', 'draft' => 'draft'][$data['action']];
        $knowledge->setStatus($source, $status, $this->admin()?->id);

        return back()->with('success', ['approved' => 'تأیید شد؛ از این پس دستیار می‌تواند از آن استفاده کند.', 'archived' => 'بایگانی شد.', 'draft' => 'به پیش‌نویس برگشت.'][$status]);
    }

    public function destroySource(KnowledgeSource $source, KnowledgeService $knowledge): RedirectResponse
    {
        $this->authorizeAbility('manage_knowledge');
        $this->own($source);
        $knowledge->delete($source, $this->admin()?->id);

        return redirect()->route('admin.smart-instagram.knowledge.index', ['tab' => 'sources'])->with('success', 'منبع دانش حذف شد.');
    }

    public function playground(Request $request, SalesAssistant $assistant): JsonResponse
    {
        $this->authorizeAbility('view');
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'use_draft' => ['nullable', 'boolean'],
            'persona_prompt' => ['nullable', 'string', 'max:12000'],
            'tone' => ['nullable', 'in:friendly,formal,energetic,luxury,calm'],
            'reply_length' => ['nullable', 'in:short,medium,long'],
        ]);
        if (!config('smart_instagram.ai.enabled')) {
            return response()->json(['ok' => false, 'message' => 'هوش مصنوعی در تنظیمات سرور خاموش است.'], 422);
        }

        $draft = $request->boolean('use_draft') ? array_filter($data, fn ($v, $k) => in_array($k, ['persona_prompt', 'tone', 'reply_length'], true) && filled($v), ARRAY_FILTER_USE_BOTH) : null;

        return response()->json($assistant->playground($data['message'], $draft ?: null));
    }

    private function own(KnowledgeSource $source): void
    {
        abort_unless((int) $source->workspace_id === $this->ws(), 404);
    }
}
