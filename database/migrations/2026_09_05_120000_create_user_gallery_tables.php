<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_gallery_configs', function (Blueprint $table): void {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('retention_days')->default(60);
            $table->unsignedInteger('max_items_per_user')->default(50);
            $table->unsignedInteger('max_storage_mb')->default(100);
            $table->timestamps();
        });

        DB::table('user_gallery_configs')->insert([
            'id' => 1,
            'enabled' => true,
            'retention_days' => 60,
            'max_items_per_user' => 50,
            'max_storage_mb' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('user_gallery_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('consent_revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_gallery_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 32);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('original_path');
            $table->string('preview_path')->nullable();
            $table->string('disk', 64)->default('user_gallery');
            $table->string('mime_type', 120)->nullable();
            $table->string('preview_mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamp('expires_at')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'source_type']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('user_gallery_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_gallery_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40);
            $table->string('source_type', 32)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_gallery_events');
        Schema::dropIfExists('user_gallery_items');
        Schema::dropIfExists('user_gallery_settings');
        Schema::dropIfExists('user_gallery_configs');
    }
};
