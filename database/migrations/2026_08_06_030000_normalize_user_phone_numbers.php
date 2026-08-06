<?php

use App\Support\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeTable('users');
        $this->normalizeTable('otps');
    }

    /**
     * شماره‌های قدیمی را بدون شکستن unique به قالب استاندارد منتقل می‌کند.
     */
    private function normalizeTable(string $table): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        DB::table($table)
            ->whereNotNull('phone')
            ->where('phone', '<>', '')
            ->select(['id', 'phone'])
            ->orderBy('id')
            ->get()
            ->each(function (object $row) use ($table): void {
                $normalized = PhoneNumber::normalize((string) $row->phone);

                if (!PhoneNumber::isValid($normalized) || $normalized === $row->phone) {
                    return;
                }

                $hasConflict = DB::table($table)
                    ->where('phone', $normalized)
                    ->where('id', '<>', $row->id)
                    ->exists();

                if (!$hasConflict) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['phone' => $normalized]);
                }
            });
    }

    public function down(): void
    {
        // نرمال‌سازی داده برگشت‌پذیر نیست و down عمداً تغییری ایجاد نمی‌کند.
    }
};
