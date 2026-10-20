<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_import_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('source_url');
            $table->string('source_host', 190);
            $table->string('adapter', 40);
            $table->text('prompt_amendment');
            $table->string('text_model', 160)->default('openai/gpt-4o-mini');
            $table->string('status', 24)->default('ready')->index();
            $table->unsignedInteger('discovered_count')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('product_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('product_import_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('run_id')->constrained('product_import_runs')->cascadeOnDelete();
            $table->text('source_url');
            $table->string('source_key', 64);
            $table->string('source_title', 255)->nullable();
            $table->json('payload');
            $table->string('status', 24)->default('pending')->index();
            $table->unsignedSmallInteger('products_created')->default(0);
            $table->json('product_ids')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['run_id', 'source_key']);
            $table->index(['run_id', 'status', 'id']);
        });

        Schema::create('product_import_settings', function (Blueprint $table): void {
            $table->id();
            $table->text('prompt_amendment');
            $table->string('text_model', 160)->default('openai/gpt-4o-mini');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_import_settings');
        Schema::dropIfExists('product_import_items');
        Schema::dropIfExists('product_import_runs');
    }
};
