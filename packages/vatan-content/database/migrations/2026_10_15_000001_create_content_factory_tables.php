<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| کارخانه‌ی محتوای وطن — جدول‌ها (پیشوند cf_)
| زنجیره: محصول/کمپین ← تقویم ← آیتم ← تولید ← نسخه ← اصلاحیه ← انتشار ← آمار ← یادگیری
| همه‌ی جدول‌های اصلی workspace_id دارند تا بعداً برای هر مشتری کسب‌وکار جدا کار کند.
*/
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cf_calendars')) {
            Schema::create('cf_calendars', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('workspace_id')->default(1)->index();
                $t->string('title');
                $t->string('version', 20)->default('v1');
                $t->date('starts_on')->nullable();
                $t->date('ends_on')->nullable();
                $t->string('goal')->nullable();
                $t->string('status', 20)->default('draft')->index();
                $t->json('summary')->nullable();
                $t->json('brief')->nullable();
                $t->string('source_file')->nullable();
                $t->unsignedBigInteger('parent_id')->nullable();
                $t->timestamp('approved_at')->nullable();
                $t->string('approved_by')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('cf_calendar_items')) {
            Schema::create('cf_calendar_items', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('workspace_id')->default(1)->index();
                $t->unsignedBigInteger('calendar_id')->index();
                $t->string('code', 20);
                $t->date('publish_date')->index();
                $t->string('publish_time', 10)->nullable();
                $t->string('platform', 30)->default('اینستاگرام');
                $t->string('format', 30);
                $t->string('pillar', 60)->nullable();
                $t->string('sector', 80)->nullable();
                $t->string('audience')->nullable();
                $t->string('goal', 60)->nullable();
                $t->text('idea')->nullable();
                $t->string('hook')->nullable();
                $t->string('cta')->nullable();
                $t->string('style')->nullable();
                $t->string('style_slug', 80)->nullable();
                $t->string('music_mood')->nullable();
                $t->string('prompt_version', 20)->nullable();
                $t->json('stories')->nullable();
                $t->text('note')->nullable();
                $t->string('production_status', 20)->default('queued');
                $t->string('approval_status', 20)->default('pending');
                $t->string('tracking_url')->nullable();
                $t->json('result')->nullable();
                $t->unsignedInteger('sort')->default(0);
                $t->timestamps();
                $t->unique(['calendar_id', 'code']);
            });
        }
        if (! Schema::hasTable('cf_styles')) {
            Schema::create('cf_styles', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('workspace_id')->default(1)->index();
                $t->string('slug', 80);
                $t->string('name_fa');
                $t->string('name_en')->nullable();
                $t->text('description')->nullable();
                $t->json('goals')->nullable();
                $t->json('sectors')->nullable();
                $t->string('duration', 30)->nullable();
                $t->string('aspect', 10)->default('9:16');
                $t->string('shots', 30)->nullable();
                $t->string('motion')->nullable();
                $t->string('text_style')->nullable();
                $t->string('hook_family')->nullable();
                $t->string('cta_family')->nullable();
                $t->string('music_hint')->nullable();
                $t->string('engine')->nullable();
                $t->string('status', 20)->default('pending')->index();
                $t->unsignedInteger('use_count')->default(0);
                $t->decimal('score', 6, 2)->nullable();
                $t->decimal('confidence', 4, 2)->nullable();
                $t->timestamp('last_used_at')->nullable();
                $t->json('meta')->nullable();
                $t->timestamps();
                $t->unique(['workspace_id', 'slug']);
            });
        }
        if (! Schema::hasTable('cf_references')) {
            Schema::create('cf_references', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('workspace_id')->default(1)->index();
                $t->unsignedBigInteger('style_id')->nullable()->index();
                $t->string('slug', 120);
                $t->string('kind', 30)->default('style_reference');
                $t->string('source_path')->nullable();
                $t->decimal('duration', 8, 2)->nullable();
                $t->unsignedInteger('width')->nullable();
                $t->unsignedInteger('height')->nullable();
                $t->unsignedInteger('shot_count')->nullable();
                $t->decimal('avg_shot_len', 8, 2)->nullable();
                $t->string('motion_level', 20)->nullable();
                $t->unsignedInteger('tempo_bpm')->nullable();
                $t->string('energy', 20)->nullable();
                $t->json('colors')->nullable();
                $t->string('sheet_path')->nullable();
                $t->text('analysis')->nullable();
                $t->json('meta')->nullable();
                $t->string('status', 30)->default('analyzed_pending_review');
                $t->timestamps();
                $t->unique(['workspace_id', 'slug']);
            });
        }
        if (! Schema::hasTable('cf_music')) {
            Schema::create('cf_music', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('workspace_id')->default(1)->index();
                $t->string('slug', 120);
                $t->string('title');
                $t->string('source', 30)->default('file');
                $t->string('source_path')->nullable();
                $t->string('audio_path')->nullable();
                $t->decimal('duration', 8, 2)->nullable();
                $t->unsignedInteger('tempo_bpm')->nullable();
                $t->string('energy', 20)->nullable();
                $t->string('mood')->nullable();
                $t->string('genre')->nullable();
                $t->json('sectors')->nullable();
                $t->string('license_status', 20)->default('unknown');
                $t->boolean('commercial_ok')->default(false);
                $t->text('license_note')->nullable();
                $t->unsignedInteger('use_count')->default(0);
                $t->timestamp('last_used_at')->nullable();
                $t->decimal('score', 6, 2)->nullable();
                $t->string('status', 20)->default('active');
                $t->json('meta')->nullable();
                $t->timestamps();
                $t->unique(['workspace_id', 'slug']);
            });
        }
        if (! Schema::hasTable('cf_productions')) {
            Schema::create('cf_productions', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('workspace_id')->default(1)->index();
                $t->unsignedBigInteger('calendar_item_id')->nullable()->index();
                $t->string('code', 40)->nullable();
                $t->string('title');
                $t->string('format', 30)->nullable();
                $t->string('style_slug', 80)->nullable();
                $t->unsignedBigInteger('music_id')->nullable();
                $t->string('status', 20)->default('queued')->index();
                $t->json('gene')->nullable();
                $t->text('script')->nullable();
                $t->text('caption')->nullable();
                $t->text('hashtags')->nullable();
                $t->string('cover_path')->nullable();
                $t->unsignedInteger('current_version')->default(0);
                $t->decimal('cost_usd', 10, 4)->default(0);
                $t->unsignedInteger('build_seconds')->nullable();
                $t->timestamp('published_at')->nullable();
                $t->string('ig_media_id')->nullable();
                $t->string('ig_permalink')->nullable();
                $t->text('review_note')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('cf_production_versions')) {
            Schema::create('cf_production_versions', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('production_id')->index();
                $t->unsignedInteger('version');
                $t->string('video_path')->nullable();
                $t->string('cover_path')->nullable();
                $t->json('stories')->nullable();
                $t->json('slides')->nullable();
                $t->decimal('duration', 8, 2)->nullable();
                $t->text('notes')->nullable();
                $t->json('qc')->nullable();
                $t->string('status', 20)->default('review');
                $t->timestamps();
                $t->unique(['production_id', 'version']);
            });
        }
        if (! Schema::hasTable('cf_segment_edits')) {
            Schema::create('cf_segment_edits', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('production_id')->index();
                $t->unsignedInteger('version');
                $t->decimal('start_sec', 8, 2);
                $t->decimal('end_sec', 8, 2);
                $t->string('kind', 20)->default('edit');
                $t->text('note');
                $t->string('status', 20)->default('open');
                $t->unsignedInteger('resolved_version')->nullable();
                $t->string('created_by')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('cf_prompt_versions')) {
            Schema::create('cf_prompt_versions', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('workspace_id')->default(1)->index();
                $t->string('layer', 60)->index();
                $t->string('title');
                $t->unsignedInteger('version')->default(1);
                $t->longText('body');
                $t->string('status', 20)->default('draft');
                $t->unsignedBigInteger('parent_id')->nullable();
                $t->json('depends_on')->nullable();
                $t->string('editor')->nullable();
                $t->text('notes')->nullable();
                $t->unsignedInteger('use_count')->default(0);
                $t->decimal('score', 6, 2)->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('cf_metric_snapshots')) {
            Schema::create('cf_metric_snapshots', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('production_id')->index();
                $t->string('window', 10);
                $t->timestamp('captured_at');
                $t->json('metrics')->nullable();
                $t->json('raw')->nullable();
                $t->timestamps();
                $t->unique(['production_id', 'window']);
            });
        }
        if (! Schema::hasTable('cf_insights')) {
            Schema::create('cf_insights', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('workspace_id')->default(1)->index();
                $t->string('title');
                $t->text('body');
                $t->unsignedInteger('sample_size')->default(0);
                $t->string('confidence', 20)->default('نامشخص');
                $t->string('period')->nullable();
                $t->json('evidence')->nullable();
                $t->text('suggestion')->nullable();
                $t->string('status', 20)->default('pending');
                $t->string('decided_by')->nullable();
                $t->timestamp('decided_at')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('cf_telegram_links')) {
            Schema::create('cf_telegram_links', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('workspace_id')->default(1)->index();
                $t->string('chat_id', 40);
                $t->string('thread_id', 40)->nullable();
                $t->string('message_id', 40)->nullable();
                $t->string('file_id')->nullable();
                $t->string('sender')->nullable();
                $t->string('topic', 40)->nullable();
                $t->string('linkable_type')->nullable();
                $t->unsignedBigInteger('linkable_id')->nullable();
                $t->json('payload')->nullable();
                $t->timestamps();
                $t->index(['linkable_type', 'linkable_id']);
            });
        }
        if (! Schema::hasTable('cf_ai_usage')) {
            Schema::create('cf_ai_usage', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('workspace_id')->default(1)->index();
                $t->string('channel', 30)->default('openrouter');
                $t->string('model')->nullable();
                $t->string('purpose', 80)->nullable();
                $t->unsignedInteger('input_tokens')->default(0);
                $t->unsignedInteger('output_tokens')->default(0);
                $t->decimal('cost_usd', 10, 5)->default(0);
                $t->json('meta')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('cf_settings')) {
            Schema::create('cf_settings', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('workspace_id')->default(1);
                $t->string('key', 80);
                $t->json('value')->nullable();
                $t->timestamps();
                $t->unique(['workspace_id', 'key']);
            });
        }
    }

    public function down(): void
    {
        foreach (['cf_settings', 'cf_ai_usage', 'cf_telegram_links', 'cf_insights', 'cf_metric_snapshots', 'cf_prompt_versions', 'cf_segment_edits',
            'cf_production_versions', 'cf_productions', 'cf_music', 'cf_references', 'cf_styles', 'cf_calendar_items', 'cf_calendars'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
