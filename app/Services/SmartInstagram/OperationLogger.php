<?php

namespace App\Services\SmartInstagram;

use App\Models\SmartInstagram\OperationLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/** لاگ عملیات حساس (پروپوزال ۷ — instagram_operation_logs). هرگز توکن یا متن کامل پیام ثبت نمی‌کند. */
class OperationLogger
{
    private const SENSITIVE_KEYS = ['access_token', 'token', 'secret', 'password', 'credentials', 'authorization'];

    public function __construct(private readonly WorkspaceContext $context)
    {
    }

    public function log(string $action, string $message, ?Model $subject = null, array $context = [], string $level = 'info', ?int $adminId = null): void
    {
        try {
            OperationLog::query()->create([
                'workspace_id' => $this->context->id(),
                'action' => $action,
                'level' => $level,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'admin_id' => $adminId ?? auth('admin')->id(),
                'message' => mb_substr($message, 0, 500),
                'context' => $this->scrub($context),
            ]);
        } catch (\Throwable $e) {
            Log::warning('smart-instagram: operation log failed', ['action' => $action, 'error' => $e->getMessage()]);
        }
    }

    public function error(string $action, string $message, ?Model $subject = null, array $context = []): void
    {
        $this->log($action, $message, $subject, $context, 'error');
    }

    private function scrub(array $context): array
    {
        $flat = Arr::dot($context);
        foreach ($flat as $key => $value) {
            foreach (self::SENSITIVE_KEYS as $needle) {
                if (str_contains(strtolower((string) $key), $needle)) {
                    $flat[$key] = '***';
                }
            }
        }

        return Arr::undot($flat);
    }
}
