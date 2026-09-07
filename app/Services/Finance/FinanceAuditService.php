<?php

namespace App\Services\Finance;

use App\Models\FinanceAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class FinanceAuditService
{
    public function record(Model|string $subject, string $action, ?array $before = null, ?array $after = null): void
    {
        if (! Schema::hasTable('finance_audit_logs')) {
            return;
        }

        $request = app()->bound('request') ? request() : null;

        FinanceAuditLog::create([
            'auditable_type' => $subject instanceof Model ? $subject::class : $subject,
            'auditable_id' => $subject instanceof Model ? $subject->getKey() : null,
            'action' => $action,
            'admin_id' => auth('admin')->id(),
            'before_data' => $before,
            'after_data' => $after,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
