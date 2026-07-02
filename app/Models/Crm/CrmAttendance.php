<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmAttendance extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'crm_attendance';

    protected $fillable = [
        'personnel_id',
        'date',
        'check_in',
        'check_out',
        'total_hours',
        'note',
    ];

    protected $casts = [
        'total_hours' => 'float',
    ];

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(CrmPersonnel::class, 'personnel_id');
    }

    // ─── Helper: محاسبه ساعت کارکرد ─────────────────────────

    public static function calcHours(?string $checkIn, ?string $checkOut): ?float
    {
        if (! $checkIn || ! $checkOut) return null;
        [$h1, $m1] = array_map('intval', explode(':', $checkIn));
        [$h2, $m2] = array_map('intval', explode(':', $checkOut));
        $mins = ($h2 * 60 + $m2) - ($h1 * 60 + $m1);
        return $mins > 0 ? round($mins / 60, 2) : null;
    }
}
