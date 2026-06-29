<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['urgent', 'high', 'medium', 'low'])->default('medium');
            $table->enum('status', ['backlog', 'todo', 'inprogress', 'done'])->default('todo');
            $table->boolean('done')->default(false);
            $table->string('deadline', 20)->nullable();   // تاریخ شمسی
            $table->string('start_date', 20)->nullable(); // تاریخ شمسی
            $table->uuid('assignee_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('project_id')
                  ->references('id')->on('crm_projects')
                  ->cascadeOnDelete();

            $table->foreign('assignee_id')
                  ->references('id')->on('crm_personnel')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_tasks');
    }
};
