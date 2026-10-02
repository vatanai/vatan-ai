<?php

namespace App\Services\SmartInstagram\Ai;

use App\Models\SmartInstagram\KnowledgeChunk;
use App\Models\SmartInstagram\KnowledgeSource;
use App\Services\SmartInstagram\PersianText;
use Illuminate\Support\Collection;

/**
 * بازیابی «فقط دانش تأییدشده‌ی همان برند» (پروپوزال ۸.۴).
 * امتیازدهی واژگانی سبک (شبیه BM25) روی متن نرمال‌شده‌ی فارسی؛ بدون سرویس برداری بیرونی.
 */
class KnowledgeRetriever
{
    private const CATEGORY_BOOST = ['approved_reply' => 1.35, 'faq' => 1.25, 'pricing' => 1.15, 'policy' => 1.1];

    /**
     * @return Collection<int,array{chunk_id:int,source_id:int,title:string,category:string,content:string,score:float}>
     */
    public function search(int $workspaceId, string $query, ?int $limit = null): Collection
    {
        $limit ??= (int) config('smart_instagram.ai.knowledge_chunks', 6);
        $tokens = array_values(array_unique(PersianText::tokens($query)));
        $usableIds = KnowledgeSource::query()->where('workspace_id', $workspaceId)->usableByAi()->pluck('id');
        if ($usableIds->isEmpty()) {
            return collect();
        }

        // دانش پایه‌ی برند همیشه در دسترس است حتی وقتی پرسش کلمه‌ی کلیدی ندارد.
        $base = KnowledgeChunk::query()
            ->whereIn('source_id', KnowledgeSource::query()->whereIn('id', $usableIds)->where('category', 'brand')->pluck('id'))
            ->orderBy('position')
            ->limit(1)
            ->get();

        $candidates = collect();
        if ($tokens) {
            $candidates = KnowledgeChunk::query()
                ->where('workspace_id', $workspaceId)
                ->whereIn('source_id', $usableIds)
                ->where(function ($q) use ($tokens): void {
                    foreach (array_slice($tokens, 0, 10) as $token) {
                        $q->orWhere('search_text', 'like', '%'.$token.'%');
                    }
                })
                ->limit(500)
                ->get(['id', 'source_id', 'content', 'search_text']);
        }

        $sources = KnowledgeSource::query()->whereIn('id', $candidates->pluck('source_id')->merge($base->pluck('source_id'))->unique())
            ->get(['id', 'title', 'category'])->keyBy('id');
        $total = max(1, $candidates->count());
        $docFreq = [];
        foreach ($tokens as $token) {
            $docFreq[$token] = max(1, $candidates->filter(fn ($c) => str_contains($c->search_text, $token))->count());
        }

        $scored = $candidates->map(function ($chunk) use ($tokens, $docFreq, $total, $sources) {
            $score = 0.0;
            foreach ($tokens as $token) {
                $tf = substr_count($chunk->search_text, $token);
                if ($tf > 0) {
                    $idf = log(1 + ($total - $docFreq[$token] + 0.5) / ($docFreq[$token] + 0.5));
                    $score += $idf * (($tf * 2.2) / ($tf + 1.2));
                }
            }
            $category = $sources[$chunk->source_id]->category ?? 'general';
            $score *= self::CATEGORY_BOOST[$category] ?? 1.0;

            return $this->row($chunk, $sources, $score);
        })->filter(fn ($row) => $row['score'] > 0)->sortByDesc('score')->take($limit)->values();

        foreach ($base as $chunk) {
            if (!$scored->contains('chunk_id', $chunk->id)) {
                $scored->push($this->row($chunk, $sources, 0.1));
            }
        }

        return $scored->values();
    }

    private function row($chunk, $sources, float $score): array
    {
        $source = $sources[$chunk->source_id] ?? null;

        return [
            'chunk_id' => (int) $chunk->id,
            'source_id' => (int) $chunk->source_id,
            'title' => (string) ($source->title ?? ''),
            'category' => (string) ($source->category ?? 'general'),
            'content' => (string) $chunk->content,
            'score' => round($score, 3),
        ];
    }
}
