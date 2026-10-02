<?php

namespace App\Services\SmartInstagram\Ai;

use App\Jobs\SmartInstagram\DigestKnowledgeSource;
use App\Models\SmartInstagram\AiSuggestion;
use App\Models\SmartInstagram\KnowledgeChunk;
use App\Models\SmartInstagram\KnowledgeSource;
use App\Services\SmartInstagram\OperationLogger;
use App\Services\SmartInstagram\PersianText;
use App\Services\SmartInstagram\WorkspaceContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** مخزن دانش برند (پروپوزال ۵.۶): نسخه، تأیید، اعتبار، تکه‌بندی و «یادگیری» از پاسخ‌های تأییدشده. */
class KnowledgeService
{
    public function __construct(
        private readonly WorkspaceContext $context,
        private readonly KnowledgeExtractor $extractor,
        private readonly OperationLogger $logger,
    ) {
    }

    /** @param array{title:string,category:string,content?:?string,ai_allowed?:bool,valid_until?:?string,status?:string} $data */
    public function create(array $data, ?UploadedFile $file = null, ?int $adminId = null): KnowledgeSource
    {
        $content = trim((string) ($data['content'] ?? ''));
        $storagePath = null;
        $originalName = null;
        $type = 'text';

        if ($file) {
            $content = trim($content."\n\n".$this->extractor->fromUpload($file));
            $originalName = mb_substr($file->getClientOriginalName(), 0, 190);
            $storagePath = $file->storeAs(
                'smart-instagram/'.$this->context->id().'/knowledge',
                Str::random(32).'.'.strtolower($file->getClientOriginalExtension() ?: 'txt'),
                config('smart_instagram.media.disk', 'local')
            ) ?: null;
            $type = 'file';
        }

        $status = in_array($data['status'] ?? 'draft', ['draft', 'approved'], true) ? $data['status'] : 'draft';
        $source = KnowledgeSource::query()->create([
            'workspace_id' => $this->context->id(),
            'title' => $data['title'],
            'category' => $data['category'],
            'source_type' => $type,
            'original_filename' => $originalName,
            'storage_path' => $storagePath,
            'content' => $this->extractor->clean($content),
            'ai_allowed' => (bool) ($data['ai_allowed'] ?? true),
            'valid_until' => $data['valid_until'] ?? null,
            'status' => $status,
            'approved_by' => $status === 'approved' ? $adminId : null,
            'approved_at' => $status === 'approved' ? now() : null,
            'created_by' => $adminId,
        ]);

        $this->reindex($source);
        $this->logger->log('knowledge.created', 'منبع دانش «'.$source->title.'» ثبت شد.', $source, ['status' => $status, 'type' => $type], 'info', $adminId);

        return $source;
    }

    /** ویرایش محتوا نسخه را بالا می‌برد و تأیید قبلی را باطل می‌کند تا متن تأییدنشده به AI نرسد. */
    public function update(KnowledgeSource $source, array $data, ?int $adminId = null): KnowledgeSource
    {
        $content = $this->extractor->clean((string) ($data['content'] ?? $source->content));
        $changed = hash('sha256', $content) !== $source->content_hash;

        $source->fill([
            'title' => $data['title'] ?? $source->title,
            'category' => $data['category'] ?? $source->category,
            'ai_allowed' => (bool) ($data['ai_allowed'] ?? $source->ai_allowed),
            'valid_until' => $data['valid_until'] ?? null,
            'content' => $content,
        ]);
        if ($changed) {
            $source->version++;
            $source->status = 'draft';
            $source->approved_by = null;
            $source->approved_at = null;
            $source->digest = null;
            $source->digest_status = null;
        }
        $source->save();

        if ($changed) {
            $this->reindex($source);
        }
        $this->logger->log('knowledge.updated', 'منبع دانش «'.$source->title.'» ویرایش شد'.($changed ? ' (نسخه '.$source->version.' — نیازمند تأیید دوباره)' : '').'.', $source, [], 'info', $adminId);

        return $source;
    }

    public function setStatus(KnowledgeSource $source, string $status, ?int $adminId = null): void
    {
        $source->forceFill([
            'status' => $status,
            'approved_by' => $status === 'approved' ? $adminId : $source->approved_by,
            'approved_at' => $status === 'approved' ? now() : $source->approved_at,
        ])->save();
        $this->logger->log('knowledge.status', 'وضعیت «'.$source->title.'» به '.$status.' تغییر کرد.', $source, [], 'info', $adminId);
    }

    public function delete(KnowledgeSource $source, ?int $adminId = null): void
    {
        if ($source->storage_path) {
            Storage::disk(config('smart_instagram.media.disk', 'local'))->delete($source->storage_path);
        }
        $title = $source->title;
        $source->delete(); // chunks با cascade حذف می‌شوند
        $this->logger->log('knowledge.deleted', 'منبع دانش «'.$title.'» حذف شد.', null, [], 'info', $adminId);
    }

    public function queueDigest(KnowledgeSource $source): void
    {
        $source->forceFill(['digest_status' => 'queued'])->save();
        DigestKnowledgeSource::dispatch($source->id)->onQueue(config('smart_instagram.queues.ai', 'default'));
    }

    /** یادگیری از پاسخ تأییدشده‌ی انسان: پرسش مشتری + پاسخ نهایی به‌عنوان «پاسخ تأییدشده» ذخیره می‌شود. */
    public function learnFromSuggestion(AiSuggestion $suggestion, bool $approve, ?int $adminId = null): KnowledgeSource
    {
        $suggestion->loadMissing('conversation');
        $question = (string) $suggestion->conversation?->messages()
            ->where('direction', 'in')
            ->where('created_at', '<=', $suggestion->created_at)
            ->latest('occurred_at')
            ->value('body');
        $answer = (string) ($suggestion->final_body ?: $suggestion->body);

        return $this->create([
            'title' => 'پاسخ تأییدشده: '.Str::limit($question !== '' ? $question : $answer, 60),
            'category' => 'approved_reply',
            'content' => "پرسش مشتری: {$question}\nپاسخ تأییدشده: {$answer}",
            'status' => $approve ? 'approved' : 'draft',
            'ai_allowed' => true,
        ], null, $adminId);
    }

    public function reindex(KnowledgeSource $source): void
    {
        $chunks = $this->chunk((string) $source->content);
        DB::transaction(function () use ($source, $chunks): void {
            KnowledgeChunk::query()->where('source_id', $source->id)->delete();
            $now = now();
            $rows = [];
            foreach ($chunks as $i => $chunk) {
                $rows[] = [
                    'workspace_id' => $source->workspace_id,
                    'source_id' => $source->id,
                    'position' => $i,
                    'content' => $chunk,
                    'search_text' => PersianText::normalize($source->title.' '.$chunk),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            foreach (array_chunk($rows, 200) as $batch) {
                KnowledgeChunk::query()->insert($batch);
            }
            $source->forceFill([
                'chunk_count' => count($chunks),
                'char_count' => mb_strlen((string) $source->content),
                'content_hash' => hash('sha256', (string) $source->content),
            ])->save();
        });
    }

    /** @return array<int,string> */
    public function chunk(string $text): array
    {
        $size = (int) config('smart_instagram.knowledge.chunk_chars', 900);
        $overlap = (int) config('smart_instagram.knowledge.chunk_overlap', 120);
        $paragraphs = preg_split("/\n{1,}/u", trim($text)) ?: [];
        $chunks = [];
        $current = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }
            while (mb_strlen($paragraph) > $size) {
                if ($current !== '') {
                    $chunks[] = $current;
                    $current = '';
                }
                $chunks[] = mb_substr($paragraph, 0, $size);
                $paragraph = mb_substr($paragraph, $size - $overlap);
            }
            if (mb_strlen($current) + mb_strlen($paragraph) + 1 > $size && $current !== '') {
                $chunks[] = $current;
                $tail = mb_substr($current, -$overlap);
                $current = $tail."\n".$paragraph;
            } else {
                $current = $current === '' ? $paragraph : $current."\n".$paragraph;
            }
        }
        if (trim($current) !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }
}
