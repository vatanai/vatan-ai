<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * فیچر فلگ و تنظیمات «استودیو محصول». پیش‌فرض: خاموش و فقط ادمین.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_shot_settings')) {
            Schema::create('product_shot_settings', function (Blueprint $table): void {
                $table->id();
                $table->boolean('enabled')->default(false);
                // admins / whitelist / public
                $table->string('audience', 20)->default('admins');
                $table->json('whitelist_user_ids')->nullable();
                $table->json('whitelist_phones')->nullable();
                $table->unsignedTinyInteger('max_shots_per_run')->default(6);
                $table->unsignedTinyInteger('client_concurrency')->default(1);
                $table->decimal('daily_cost_cap_usd', 10, 2)->default(5);
                $table->boolean('preflight_enabled')->default(true);
                $table->string('preflight_model')->nullable();
                $table->boolean('qc_enabled')->default(true);
                $table->string('qc_model')->nullable();
                $table->boolean('qc_auto_retry')->default(true);
                // قیمت فروش هر کردیت (تومان) برای هشدار «هزینه بیش از ۵۰٪ درآمد»
                $table->unsignedInteger('credit_price_toman')->default(585);
                $table->timestamps();
            });
        }

        if (DB::table('product_shot_settings')->count() === 0) {
            DB::table('product_shot_settings')->insert([
                'enabled' => false,
                'audience' => 'admins',
                'whitelist_user_ids' => json_encode([]),
                'whitelist_phones' => json_encode([]),
                'max_shots_per_run' => 6,
                'client_concurrency' => 1,
                'daily_cost_cap_usd' => 5,
                'preflight_enabled' => true,
                'qc_enabled' => true,
                'qc_auto_retry' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_shot_settings');
    }
};
