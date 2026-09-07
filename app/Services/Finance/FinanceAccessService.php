<?php

namespace App\Services\Finance;

use App\Models\Admin;

class FinanceAccessService
{
    public function canWrite(?Admin $admin): bool
    {
        return $admin && in_array($admin->role, ['finance', 'leader'], true);
    }

    public function canApprove(?Admin $admin): bool
    {
        return $admin?->role === 'leader';
    }

    public function ensureWrite(?Admin $admin): void
    {
        abort_unless($this->canWrite($admin), 403, 'برای ثبت یا ویرایش اطلاعات مالی دسترسی ندارید.');
    }

    public function ensureApprove(?Admin $admin): void
    {
        abort_unless($this->canApprove($admin), 403, 'تأیید یا حذف رکورد مالی فقط برای مالک مجاز است.');
    }
}
