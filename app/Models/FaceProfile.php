<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'reference_images',
        'status',
    ];

    protected $casts = [
        'reference_images' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function referenceImageEntries(): array
    {
        return collect($this->reference_images ?? [])
            ->map(function ($image) {
                if (is_string($image)) {
                    return ['path' => $image, 'mime' => null, 'size' => null];
                }

                return is_array($image) ? $image : null;
            })
            ->filter(fn ($image) => filled($image['path'] ?? null))
            ->values()
            ->all();
    }

    public function coverUrl(): ?string
    {
        $path = data_get($this->referenceImageEntries(), '0.path');

        return $path ? asset('storage/' . ltrim($path, '/')) : null;
    }
}
