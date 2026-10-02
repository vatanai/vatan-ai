<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** یک ساخت پک: عکس(های) ورودی + مجموعه‌ی شات‌های انتخاب‌شده. */
class ShotBatch extends Model
{
    protected $fillable = [
        'uuid', 'user_id', 'product_id', 'status', 'aspect_ratio', 'source_paths', 'preflight',
        'shots_total', 'credits_quoted', 'source', 'sources_deleted_at', 'completed_at',
    ];

    protected $casts = [
        'source_paths' => 'array',
        'preflight' => 'array',
        'shots_total' => 'integer',
        'credits_quoted' => 'integer',
        'sources_deleted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ShotBatch $batch): void {
            if (! $batch->uuid) {
                $batch->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShotBatchItem::class)->orderBy('sort')->orderBy('id');
    }

    /** وضعیت کلی را از وضعیت آیتم‌ها محاسبه و ذخیره می‌کند. */
    public function refreshStatus(): void
    {
        $statuses = $this->items()->pluck('status');
        if ($statuses->isEmpty()) {
            return;
        }
        $done = $statuses->filter(fn ($s) => $s === 'completed')->count();
        $failed = $statuses->filter(fn ($s) => $s === 'failed')->count();
        $open = $statuses->count() - $done - $failed;

        $status = match (true) {
            $open > 0 && ($done + $failed) > 0 => 'running',
            $open > 0 => 'pending',
            $failed === 0 => 'completed',
            $done === 0 => 'failed',
            default => 'partial',
        };
        $this->status = $status;
        if ($open === 0 && ! $this->completed_at) {
            $this->completed_at = now();
        }
        $this->save();
    }
}
