<?php

namespace App\Models\SmartInstagram;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeChunk extends Model
{
    protected $table = 'instagram_knowledge_chunks';

    protected $fillable = ['workspace_id', 'source_id', 'position', 'content', 'search_text'];

    public function source(): BelongsTo
    {
        return $this->belongsTo(KnowledgeSource::class, 'source_id');
    }
}
