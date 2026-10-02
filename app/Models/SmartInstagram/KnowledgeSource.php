<?php

namespace App\Models\SmartInstagram;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeSource extends Model
{
    protected $table = 'instagram_knowledge_sources';

    protected $fillable = [
        'workspace_id', 'title', 'category', 'source_type', 'original_filename', 'storage_path', 'content', 'content_hash',
        'char_count', 'chunk_count', 'status', 'ai_allowed', 'version', 'valid_until', 'digest', 'digest_status',
        'digested_at', 'usage_count', 'approved_by', 'approved_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'ai_allowed' => 'boolean',
            'valid_until' => 'date',
            'digest' => 'array',
            'digested_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'source_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    /** فقط دانشی که هوش مصنوعی اجازه‌ی استفاده از آن را دارد. */
    public function scopeUsableByAi(Builder $query): Builder
    {
        return $query->where('status', 'approved')
            ->where('ai_allowed', true)
            ->where(fn (Builder $q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', now()->toDateString()));
    }

    public function isUsableByAi(): bool
    {
        return $this->status === 'approved' && $this->ai_allowed && ($this->valid_until === null || !$this->valid_until->isPast() || $this->valid_until->isToday());
    }
}
