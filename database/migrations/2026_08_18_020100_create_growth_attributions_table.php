<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('growth_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('growth_link_id')->constrained('growth_links')->cascadeOnDelete();
            $table->uuid('click_event_uuid')->nullable()->index();
            $table->string('visitor_id', 64)->nullable()->index();
            // شناسه‌های هسته عمداً بدون کلید خارجی نگهداری می‌شوند تا ماژول رشد
            // چرخه ایجاد/حذف داده‌های اصلی وطن را تغییر ندهد.
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('generation_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('plan_purchase_id')->nullable()->index();
            $table->string('stage', 40)->index();
            $table->string('attribution_model', 30)->default('last_click');
            $table->boolean('is_repeat')->default(false)->index();
            $table->json('metadata')->nullable();
            $table->timestamp('attributed_at')->index();
            $table->timestamps();

            $table->unique(['stage', 'generation_id'], 'growth_attr_stage_generation_unique');
            $table->unique(['stage', 'order_id'], 'growth_attr_stage_order_unique');
            $table->unique(['stage', 'plan_purchase_id'], 'growth_attr_stage_plan_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('growth_attributions');
    }
};
