<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('studio_cost_rules')) {
            return;
        }

        Schema::create('studio_cost_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('ai_model_id')->nullable()->constrained('ai_models')->nullOnDelete();
            $table->string('media_type', 20);
            $table->string('provider', 40)->nullable();
            $table->string('resolution', 30)->nullable();
            $table->string('aspect_ratio', 20)->nullable();
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            $table->decimal('base_cost_usd', 14, 6)->default(0);
            $table->decimal('exchange_rate_toman', 20, 4)->nullable();
            $table->decimal('cost_toman', 20, 2)->nullable();
            $table->string('profit_type', 20)->default('percentage');
            $table->decimal('profit_value', 20, 4)->default(0);
            $table->unsignedInteger('credit_cost')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['media_type', 'provider', 'resolution']);
            $table->index(['product_id', 'ai_model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_cost_rules');
    }
};
