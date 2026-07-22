<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_test_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_uuid')->index();
            $table->uuid('draft_uuid')->nullable()->index();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('mode', 20)->default('quick');
            $table->string('model_id');
            $table->string('status', 20)->default('processing');
            $table->json('input_values')->nullable();
            $table->text('prompt_template')->nullable();
            $table->longText('final_prompt');
            $table->text('negative_prompt')->nullable();
            $table->json('parameters')->nullable();
            $table->string('output_path')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->unsignedBigInteger('input_tokens')->nullable();
            $table->unsignedBigInteger('output_tokens')->nullable();
            $table->unsignedBigInteger('total_tokens')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('last_test_duration_ms')->nullable()->after('estimated_time');
            $table->unsignedBigInteger('total_test_tokens')->default(0)->after('last_test_duration_ms');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['last_test_duration_ms', 'total_test_tokens']);
        });
        Schema::dropIfExists('product_test_runs');
    }
};
