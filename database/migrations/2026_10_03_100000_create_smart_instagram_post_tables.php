<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «ثبت پست» اینستاگرام هوشمند — لایه‌ی مدیریت پست، کلمه‌ی کلیدی، کارت دایرکت و شرط فالو.
 * اجرای سناریو همچنان با instagram_automation_rules است؛ این جدول‌ها فقط تنظیمات ساختاریافته،
 * نسخه‌ها، آمار پست و وضعیت جریان دایرکت (کلیک/فالو) را نگه می‌دارند. کاملاً افزایشی و idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('instagram_posts')) {
            Schema::create('instagram_posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
                $table->foreignId('channel_id')->nullable()->constrained('instagram_channels')->nullOnDelete();
                $table->string('media_id', 100);
                $table->string('shortcode', 60)->nullable()->index();
                $table->text('permalink')->nullable();
                $table->string('media_type', 30)->nullable();
                $table->string('product_type', 30)->nullable();
                $table->text('caption')->nullable();
                $table->string('cover_path')->nullable();
                $table->text('cover_source_url')->nullable();
                $table->timestamp('published_at')->nullable()->index();
                $table->unsignedInteger('like_count')->nullable();
                $table->unsignedInteger('comments_count')->nullable();
                $table->unsignedInteger('saved_count')->nullable();
                $table->unsignedInteger('shares_count')->nullable();
                $table->unsignedInteger('reach_count')->nullable();
                $table->timestamp('stats_synced_at')->nullable();
                $table->string('source', 20)->default('sync');
                $table->string('connection_status', 20)->default('verified');
                $table->text('sync_error')->nullable();
                $table->timestamps();
                $table->unique(['workspace_id', 'media_id'], 'ig_post_media_unique');
            });
        }

        if (!Schema::hasTable('instagram_post_campaigns')) {
            Schema::create('instagram_post_campaigns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
                $table->foreignId('post_id')->constrained('instagram_posts')->cascadeOnDelete();
                $table->foreignId('automation_rule_id')->nullable()->constrained('instagram_automation_rules')->nullOnDelete();
                $table->string('title');
                $table->string('status', 20)->default('draft')->index();
                $table->boolean('follow_required')->default(true);
                $table->boolean('public_reply_enabled')->default(true);
                $table->boolean('dm_enabled')->default(true);
                $table->json('settings');
                $table->unsignedInteger('version')->default(1);
                $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamps();
                $table->unique(['workspace_id', 'post_id'], 'ig_post_campaign_unique');
            });
        }

        if (!Schema::hasTable('instagram_post_campaign_keywords')) {
            Schema::create('instagram_post_campaign_keywords', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained('instagram_post_campaigns')->cascadeOnDelete();
                $table->string('keyword', 120);
                $table->string('normalized', 120);
                $table->string('match_mode', 20)->default('contains');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['campaign_id', 'normalized'], 'ig_post_keyword_unique');
            });
        }

        if (!Schema::hasTable('instagram_post_campaign_versions')) {
            Schema::create('instagram_post_campaign_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained('instagram_post_campaigns')->cascadeOnDelete();
                $table->unsignedInteger('version');
                $table->json('snapshot');
                $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
                $table->string('note')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->unique(['campaign_id', 'version'], 'ig_post_version_unique');
            });
        }

        if (!Schema::hasTable('instagram_post_flow_sessions')) {
            Schema::create('instagram_post_flow_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
                $table->foreignId('campaign_id')->constrained('instagram_post_campaigns')->cascadeOnDelete();
                $table->foreignId('contact_id')->constrained('instagram_contacts')->cascadeOnDelete();
                $table->foreignId('conversation_id')->constrained('instagram_conversations')->cascadeOnDelete();
                $table->unsignedBigInteger('comment_message_id')->nullable();
                $table->unsignedBigInteger('automation_run_id')->nullable()->index();
                $table->string('mode', 10)->default('live');
                $table->string('stage', 30)->index();
                $table->unsignedTinyInteger('follow_checks')->default(0);
                $table->string('follow_status', 20)->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->index(['contact_id', 'stage'], 'ig_flow_contact_stage_idx');
                $table->unique(['campaign_id', 'comment_message_id'], 'ig_flow_once_per_comment');
            });
        }

        if (!Schema::hasTable('instagram_post_stats_daily')) {
            Schema::create('instagram_post_stats_daily', function (Blueprint $table) {
                $table->id();
                $table->foreignId('post_id')->constrained('instagram_posts')->cascadeOnDelete();
                $table->date('day');
                $table->unsignedInteger('like_count')->nullable();
                $table->unsignedInteger('comments_count')->nullable();
                $table->unsignedInteger('saved_count')->nullable();
                $table->unsignedInteger('shares_count')->nullable();
                $table->timestamps();
                $table->unique(['post_id', 'day'], 'ig_post_stats_day_unique');
            });
        }
    }

    public function down(): void
    {
        foreach (['instagram_post_stats_daily', 'instagram_post_flow_sessions', 'instagram_post_campaign_versions', 'instagram_post_campaign_keywords', 'instagram_post_campaigns', 'instagram_posts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
