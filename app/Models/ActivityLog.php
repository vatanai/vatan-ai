<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request as RequestFacade;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'message',
        'generation_id',
        'prompt_id',
        'meta',
        'level',
        'ip_address',
        'user_agent',
        'session_id',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * ارتباط با کاربر مربوطه (ممکن است null باشد - کاربر مهمان)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ارتباط با رکورد جنریت مربوطه (در صورت وجود)
     */
    public function generation()
    {
        return $this->belongsTo(Generation::class);
    }

    /**
     * ارتباط با پرامپت/سبک مربوطه (در صورت وجود)
     */
    public function prompt()
    {
        return $this->belongsTo(Prompt::class);
    }

    /**
     * متد کمکی مرکزی برای ثبت سریع و یکپارچه‌ی هر نوع رویداد در سراسر پروژه.
     *
     * مثال استفاده:
     * ActivityLog::record(
     *     type: 'generate_success',
     *     message: 'تصویر با موفقیت تولید شد',
     *     userId: auth()->id(),
     *     level: 'success',
     *     meta: ['prompt_id' => $prompt->id],
     *     generationId: $generation->id,
     *     promptId: $prompt->id,
     * );
     */
    public static function record(
        string $type,
        string $message,
        ?int $userId = null,
        string $level = 'info',
        array $meta = [],
        ?int $generationId = null,
        ?int $promptId = null,
    ): self {
        $request = RequestFacade::instance();

        return self::create([
            'user_id'       => $userId,
            'type'          => $type,
            'message'       => $message,
            'generation_id' => $generationId,
            'prompt_id'     => $promptId,
            'meta'          => $meta,
            'level'         => $level,
            'ip_address'    => $request?->ip(),
            'user_agent'    => $request?->userAgent(),
            'session_id'    => $request?->hasSession() ? $request->session()->getId() : null,
        ]);
    }
}