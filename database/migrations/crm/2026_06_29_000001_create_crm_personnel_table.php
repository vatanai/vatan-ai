<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_personnel', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('mobile', 20)->nullable();
            $table->string('role', 60)->default('سایر');
            $table->string('email')->nullable();
            $table->string('join_date', 20)->nullable(); // تاریخ شمسی مثل 1404/01/15
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_personnel');
    }
};
