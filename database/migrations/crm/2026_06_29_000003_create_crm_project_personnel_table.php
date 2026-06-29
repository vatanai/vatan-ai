<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_project_personnel', function (Blueprint $table) {
            $table->uuid('project_id');
            $table->uuid('personnel_id');
            $table->primary(['project_id', 'personnel_id']);

            $table->foreign('project_id')
                  ->references('id')->on('crm_projects')
                  ->cascadeOnDelete();

            $table->foreign('personnel_id')
                  ->references('id')->on('crm_personnel')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_project_personnel');
    }
};
