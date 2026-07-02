<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_activity_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 20);           // project | task | attendance
            $table->uuid('personnel_id')->nullable();
            $table->string('personnel_name')->nullable(); // ذخیره نام برای تاریخچه
            $table->string('action');
            $table->uuid('project_id')->nullable();
            $table->uuid('task_id')->nullable();
            $table->timestamps();

            // کلید خارجی نرم — اگر نیرو حذف شد لاگ باقی بماند
            $table->foreign('personnel_id')
                  ->references('id')->on('crm_personnel')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activity_log');
    }
};
