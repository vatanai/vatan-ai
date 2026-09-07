<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('growth_links', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug', 80)->unique();
            $table->text('destination_url');
            $table->string('channel', 40)->index();
            $table->string('content_type', 40)->nullable();
            $table->string('campaign')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('growth_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('growth_link_id')->nullable()->constrained('growth_links')->nullOnDelete();
            $table->string('title');
            $table->string('channel', 40)->index();
            $table->string('content_type', 40)->nullable();
            $table->string('external_id')->nullable()->index();
            $table->text('external_url')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('engagements')->default(0);
            $table->unsignedBigInteger('comments')->default(0);
            $table->unsignedBigInteger('shares')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('growth_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('growth_link_id')->constrained('growth_links')->cascadeOnDelete();
            $table->uuid('event_uuid')->unique();
            $table->uuid('parent_event_uuid')->nullable()->index();
            $table->string('event_type', 30)->index();
            $table->string('visitor_id', 64)->nullable()->index();
            $table->string('session_id', 100)->nullable();
            $table->text('page_url')->nullable();
            $table->text('referrer')->nullable();
            $table->string('source')->nullable()->index();
            $table->string('medium')->nullable();
            $table->string('campaign')->nullable()->index();
            $table->string('device_type', 30)->nullable()->index();
            $table->string('operating_system', 60)->nullable();
            $table->string('browser', 60)->nullable();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('country', 80)->nullable();
            $table->string('city', 100)->nullable();
            $table->boolean('is_new_visitor')->default(false)->index();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['growth_link_id', 'event_type', 'occurred_at'], 'growth_event_link_type_time');
            $table->unique(['event_type', 'parent_event_uuid'], 'growth_event_parent_type_unique');
        });

        Schema::create('growth_link_24h_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('growth_link_id')->constrained('growth_links')->cascadeOnDelete();
            $table->timestamp('window_start');
            $table->timestamp('window_end');
            $table->unsignedBigInteger('total_clicks')->default(0);
            $table->unsignedBigInteger('unique_clicks')->default(0);
            $table->unsignedBigInteger('page_opens')->default(0);
            $table->unsignedBigInteger('failed_opens')->default(0);
            $table->decimal('success_rate', 6, 2)->default(0);
            $table->unsignedBigInteger('mobile_clicks')->default(0);
            $table->unsignedBigInteger('desktop_clicks')->default(0);
            $table->unsignedBigInteger('new_visitors')->default(0);
            $table->unsignedBigInteger('repeat_visitors')->default(0);
            $table->json('breakdowns')->nullable();
            $table->timestamps();

            $table->unique(['growth_link_id', 'window_start'], 'growth_link_window_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('growth_link_24h_snapshots');
        Schema::dropIfExists('growth_events');
        Schema::dropIfExists('growth_contents');
        Schema::dropIfExists('growth_links');
    }
};
