<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 100)->unique();
            $table->string('objective', 80)->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('budget_toman')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('marketing_scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 100)->unique();
            $table->string('channel', 40)->default('instagram')->index();
            $table->string('trigger_type', 40)->default('comment_keyword');
            $table->string('status', 30)->default('draft')->index();
            $table->unsignedInteger('active_version')->default(1);
            $table->text('description')->nullable();
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('marketing_scenario_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_scenario_id')->constrained('marketing_scenarios')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status', 30)->default('draft')->index();
            $table->string('trigger_keyword', 120)->nullable();
            $table->text('public_reply')->nullable();
            $table->text('opening_message')->nullable();
            $table->string('opening_button_label', 120)->nullable();
            $table->text('followup_message')->nullable();
            $table->string('followup_button_label', 120)->nullable();
            $table->text('followup_url')->nullable();
            $table->json('rules')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['marketing_scenario_id', 'version'], 'marketing_scenario_version_unique');
        });

        Schema::create('marketing_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();
            $table->foreignId('marketing_scenario_id')->nullable()->constrained('marketing_scenarios')->nullOnDelete();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('growth_content_id')->nullable()->index();
            $table->string('title');
            $table->string('channel', 40)->default('instagram')->index();
            $table->string('content_type', 40)->default('reel');
            $table->string('status', 30)->default('draft')->index();
            $table->string('external_id')->nullable()->index();
            $table->text('external_url')->nullable();
            $table->timestamp('publish_at')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->text('hook')->nullable();
            $table->text('caption')->nullable();
            $table->string('keyword', 120)->nullable();
            $table->text('media_path')->nullable();
            $table->text('media_url')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('marketing_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();
            $table->foreignId('marketing_content_id')->nullable()->constrained('marketing_contents')->nullOnDelete();
            $table->string('code', 100)->unique();
            $table->string('title');
            $table->text('destination_url');
            $table->string('channel', 40)->default('instagram')->index();
            $table->string('status', 30)->default('active')->index();
            $table->json('utm')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('marketing_operation_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('run_uuid')->unique();
            $table->string('operation_type', 60)->index();
            $table->string('status', 30)->default('queued')->index();
            $table->unsignedInteger('attempt')->default(1);
            $table->string('idempotency_key', 191)->nullable()->unique();
            $table->string('external_execution_id')->nullable()->index();
            $table->foreignId('marketing_campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();
            $table->foreignId('marketing_content_id')->nullable()->constrained('marketing_contents')->nullOnDelete();
            $table->foreignId('marketing_scenario_id')->nullable()->constrained('marketing_scenarios')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('marketing_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->uuid('parent_event_uuid')->nullable()->index();
            $table->string('event_type', 60)->index();
            $table->string('channel', 40)->nullable()->index();
            $table->string('processing_status', 30)->default('received')->index();
            $table->foreignId('marketing_campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();
            $table->foreignId('marketing_content_id')->nullable()->constrained('marketing_contents')->nullOnDelete();
            $table->foreignId('marketing_scenario_id')->nullable()->constrained('marketing_scenarios')->nullOnDelete();
            $table->foreignId('marketing_operation_run_id')->nullable()->constrained('marketing_operation_runs')->nullOnDelete();
            $table->foreignId('marketing_link_id')->nullable()->constrained('marketing_links')->nullOnDelete();
            $table->string('external_id')->nullable()->index();
            $table->string('actor_ref', 191)->nullable()->index();
            $table->string('visitor_ref', 191)->nullable()->index();
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'channel', 'occurred_at'], 'marketing_event_type_channel_time');
        });

        Schema::create('marketing_cost_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_operation_run_id')->nullable()->constrained('marketing_operation_runs')->nullOnDelete();
            $table->foreignId('marketing_campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();
            $table->foreignId('marketing_content_id')->nullable()->constrained('marketing_contents')->nullOnDelete();
            $table->string('provider', 80)->index();
            $table->string('service', 100)->index();
            $table->decimal('units', 18, 6)->default(0);
            $table->string('unit', 40)->nullable();
            $table->decimal('unit_cost_usd', 18, 8)->nullable();
            $table->decimal('fx_rate_toman', 18, 2)->nullable();
            $table->unsignedBigInteger('cost_toman')->default(0);
            $table->string('status', 30)->default('estimated')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('incurred_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_cost_events');
        Schema::dropIfExists('marketing_events');
        Schema::dropIfExists('marketing_operation_runs');
        Schema::dropIfExists('marketing_links');
        Schema::dropIfExists('marketing_contents');
        Schema::dropIfExists('marketing_scenario_versions');
        Schema::dropIfExists('marketing_scenarios');
        Schema::dropIfExists('marketing_campaigns');
    }
};
