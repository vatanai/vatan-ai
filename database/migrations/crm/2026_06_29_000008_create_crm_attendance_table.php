<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_attendance', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('personnel_id');
            $table->string('date', 20);        // تاریخ شمسی مثل 1404/04/08
            $table->string('check_in', 5)->nullable();  // مثل 08:30
            $table->string('check_out', 5)->nullable(); // مثل 17:00
            $table->decimal('total_hours', 5, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('personnel_id')
                  ->references('id')->on('crm_personnel')
                  ->cascadeOnDelete();

            // یک نفر نمیتونه دو رکورد برای یک روز داشته باشه
            $table->unique(['personnel_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_attendance');
    }
};
