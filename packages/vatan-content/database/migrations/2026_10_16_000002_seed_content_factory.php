<?php

use Illuminate\Database\Migrations\Migration;

/* داده‌ی پایه‌ی کارخانه‌ی محتوا (سبک‌ها، ۱۵ لایه‌ی پرامپت، تقویم تا ۳۰ آبان، یادگیری اولیه) — idempotent */
return new class extends Migration
{
    public function up(): void
    {
        require_once dirname(__DIR__, 2).'/bootstrap.php';
        \Vatan\Content\Support\Seed::run((int) config('content-factory.workspace_id', 1));
    }

    public function down(): void
    {
    }
};
