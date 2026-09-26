<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_journey_settings', function (Blueprint $table): void {
            $table->string('key', 80)->primary();
            $table->string('label', 180);
            $table->string('value', 120);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('customer_journey_settings')->insert([
            ['key' => 'auto_credit_percent', 'label' => 'درصد اعتبار شارژ خودکار', 'value' => '50', 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'station_size', 'label' => 'اندازه هر ایستگاه اعتبار', 'value' => '120', 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'final_credit_threshold', 'label' => 'آستانه اعتبار پایانی', 'value' => '120', 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'inactivity_days', 'label' => 'روزهای توقف پیش از پیگیری', 'value' => '7', 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'max_daily_tasks', 'label' => 'حداکثر تسک روزانه هر کاربر', 'value' => '1', 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_journey_settings');
    }
};
