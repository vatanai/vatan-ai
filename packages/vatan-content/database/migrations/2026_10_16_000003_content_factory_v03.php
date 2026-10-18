<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| کارخانه‌ی محتوا v0.3 — پرامپت جامع ساخت برای هر پست (بخش‌بندی‌شده و قابل ویرایش) و ردگیری پرامپت استفاده‌شده در هر نسخه.
*/
return new class extends Migration
{
    private function add(string $table, string $col, callable $def): void
    {
        if (Schema::hasTable($table) && ! Schema::hasColumn($table, $col)) {
            Schema::table($table, fn (Blueprint $t) => $def($t));
        }
    }

    public function up(): void
    {
        $this->add('cf_calendar_items', 'build_prompt', fn ($t) => $t->json('build_prompt')->nullable());
        $this->add('cf_productions', 'build_prompt', fn ($t) => $t->longText('build_prompt')->nullable());
        $this->add('cf_production_versions', 'build_prompt', fn ($t) => $t->longText('build_prompt')->nullable());
        $this->add('cf_production_versions', 'build_seconds', fn ($t) => $t->unsignedInteger('build_seconds')->nullable());
        $this->add('cf_production_versions', 'cost_usd', fn ($t) => $t->decimal('cost_usd', 10, 4)->default(0));
        $this->add('cf_styles', 'cover_path', fn ($t) => $t->string('cover_path')->nullable());
    }

    public function down(): void
    {
    }
};
