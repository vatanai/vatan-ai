<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('telegram_segments')) {
            return;
        }

        Schema::create('telegram_segments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('name', 180);
            $table->json('definition');
            $table->unsignedInteger('user_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_segments');
    }
};
