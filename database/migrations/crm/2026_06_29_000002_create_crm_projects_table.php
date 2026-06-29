<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('emoji', 10)->default('📁');
            $table->enum('status', ['planning', 'waiting', 'inprogress', 'done', 'stopped'])->default('planning');
            $table->text('description')->nullable();
            $table->string('deadline', 20)->nullable();   // تاریخ شمسی
            $table->string('start_date', 20)->nullable(); // تاریخ شمسی
            $table->string('end_date', 20)->nullable();   // تاریخ شمسی
            $table->uuid('manager_id')->nullable();
            $table->boolean('archived')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('manager_id')
                  ->references('id')->on('crm_personnel')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_projects');
    }
};
