<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_task_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id');
            $table->string('author_name'); // نام نویسنده کامنت (از admin یا متن آزاد)
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('task_id')
                  ->references('id')->on('crm_tasks')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_task_comments');
    }
};
