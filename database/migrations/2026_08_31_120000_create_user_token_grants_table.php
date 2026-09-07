<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_token_grants')) {
            return;
        }

        Schema::create('user_token_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('token_log_id')->nullable()->constrained('token_logs')->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->unsignedInteger('amount');
            $table->unsignedInteger('remaining_amount');
            $table->timestamp('expires_at')->nullable();
            $table->string('source', 40)->default('manual_credit');
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
            $table->index(['user_id', 'remaining_amount']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_token_grants');
    }
};
