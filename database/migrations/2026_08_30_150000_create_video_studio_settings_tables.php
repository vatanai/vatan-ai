<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('video_studio_settings')) {
            Schema::create('video_studio_settings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('source_mode', 20)->default('auto');
                $table->text('source_url')->nullable();
                $table->boolean('auto_enabled')->default(false);
                $table->boolean('approval_required')->default(true);
                $table->boolean('auto_generate_hook')->default(true);
                $table->boolean('auto_generate_caption')->default(true);
                $table->boolean('auto_generate_keyword')->default(true);
                $table->text('hook_guidelines')->nullable();
                $table->text('caption_guidelines')->nullable();
                $table->string('keyword', 80)->nullable();
                $table->text('dm_template')->nullable();
                $table->string('font_family', 80)->default('B_Yekan');
                $table->string('aspect_ratio', 20)->default('9:16');
                $table->timestamps();
                $table->unique('product_id');
            });
        }

        if (!Schema::hasTable('video_hook_inspirations')) {
            Schema::create('video_hook_inspirations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title', 160);
                $table->text('hook_text');
                $table->string('tags', 500)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['product_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('video_hook_inspirations');
        Schema::dropIfExists('video_studio_settings');
    }
};
