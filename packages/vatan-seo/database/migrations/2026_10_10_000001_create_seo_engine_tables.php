<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vatan SEO Engine — جداول پایه (نسخه 0.1.0)
 * همه‌ی جداول با پیشوند seo_ هستند تا با جداول میزبان تداخل نداشته باشند.
 * migration ایدمپوتنت است: اگر جدولی وجود داشته باشد دوباره ساخته نمی‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seo_sites')) {
            Schema::create('seo_sites', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('domain')->unique();
                $t->string('base_url');
                $t->string('platform', 30)->default('laravel_local'); // laravel_local | laravel_api | wordpress
                $t->text('connector_config')->nullable(); // encrypted json
                $t->string('gsc_property')->nullable();
                $t->string('ga4_property')->nullable();
                $t->decimal('monthly_budget_usd', 8, 2)->default(0);
                $t->string('niche')->nullable();
                $t->text('brand_brief')->nullable(); // معرفی برند برای پرامپت‌ها
                $t->json('settings')->nullable();
                $t->date('started_on')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('seo_keyword_clusters')) {
            Schema::create('seo_keyword_clusters', function (Blueprint $t) {
                $t->id();
                $t->foreignId('site_id')->constrained('seo_sites')->cascadeOnDelete();
                $t->string('name');
                $t->string('intent', 30)->nullable();
                $t->string('pillar_url')->nullable();
                $t->text('summary')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('seo_keywords')) {
            Schema::create('seo_keywords', function (Blueprint $t) {
                $t->id();
                $t->foreignId('site_id')->constrained('seo_sites')->cascadeOnDelete();
                $t->foreignId('cluster_id')->nullable()->constrained('seo_keyword_clusters')->nullOnDelete();
                $t->string('keyword');
                $t->string('normalized')->index();
                $t->string('status', 20)->default('candidate'); // candidate | target | archived
                $t->string('source', 30)->default('manual'); // manual | products | gsc | autocomplete | ai
                $t->string('intent', 30)->nullable(); // informational | commercial | transactional | navigational
                $t->unsignedTinyInteger('priority')->default(3);
                $t->unsignedTinyInteger('ai_score')->nullable(); // ۰ تا ۱۰۰ — امتیاز فرصت
                $t->string('target_url')->nullable();
                $t->unsignedBigInteger('product_id')->nullable();
                $t->unsignedInteger('search_volume')->nullable();
                $t->unsignedTinyInteger('difficulty')->nullable();
                $t->decimal('current_position', 6, 2)->nullable();
                $t->decimal('previous_position', 6, 2)->nullable();
                $t->decimal('best_position', 6, 2)->nullable();
                $t->decimal('start_position', 6, 2)->nullable();
                $t->unsignedInteger('clicks_28d')->default(0);
                $t->unsignedInteger('impressions_28d')->default(0);
                $t->string('ranking_url')->nullable();
                $t->text('notes')->nullable();
                $t->json('meta')->nullable();
                $t->timestamp('targeted_at')->nullable();
                $t->timestamp('rank_checked_at')->nullable();
                $t->timestamps();
                $t->unique(['site_id', 'normalized']);
                $t->index(['site_id', 'status']);
            });
        }

        if (! Schema::hasTable('seo_keyword_ranks')) {
            Schema::create('seo_keyword_ranks', function (Blueprint $t) {
                $t->id();
                $t->foreignId('keyword_id')->constrained('seo_keywords')->cascadeOnDelete();
                $t->date('date');
                $t->string('source', 20)->default('gsc'); // gsc | dataforseo | mizfa
                $t->decimal('position', 6, 2)->nullable();
                $t->unsignedInteger('clicks')->default(0);
                $t->unsignedInteger('impressions')->default(0);
                $t->string('url')->nullable();
                $t->timestamps();
                $t->unique(['keyword_id', 'date', 'source']);
            });
        }

        if (! Schema::hasTable('seo_daily_metrics')) {
            Schema::create('seo_daily_metrics', function (Blueprint $t) {
                $t->id();
                $t->foreignId('site_id')->constrained('seo_sites')->cascadeOnDelete();
                $t->date('date');
                $t->unsignedInteger('clicks')->default(0);
                $t->unsignedInteger('impressions')->default(0);
                $t->decimal('ctr', 6, 4)->default(0);
                $t->decimal('position', 6, 2)->nullable();
                $t->unsignedInteger('queries_count')->default(0);
                $t->unsignedInteger('pages_count')->default(0);
                $t->json('position_buckets')->nullable(); // {top3, top10, top20, rest}
                $t->timestamps();
                $t->unique(['site_id', 'date']);
            });
        }

        if (! Schema::hasTable('seo_query_metrics')) {
            // جزئیات کوئری × صفحه × روز از سرچ کنسول (برای فرصت‌ها، همنوع‌خواری و رتبه)
            Schema::create('seo_query_metrics', function (Blueprint $t) {
                $t->id();
                $t->foreignId('site_id')->constrained('seo_sites')->cascadeOnDelete();
                $t->date('date');
                $t->string('query', 500);
                $t->string('query_hash', 40);
                $t->string('page', 700);
                $t->string('page_hash', 40);
                $t->unsignedInteger('clicks')->default(0);
                $t->unsignedInteger('impressions')->default(0);
                $t->decimal('ctr', 6, 4)->default(0);
                $t->decimal('position', 6, 2)->nullable();
                $t->unique(['site_id', 'date', 'query_hash', 'page_hash'], 'seo_qm_unique');
                $t->index(['site_id', 'query_hash']);
                $t->index(['site_id', 'page_hash']);
            });
        }

        if (! Schema::hasTable('seo_tasks')) {
            Schema::create('seo_tasks', function (Blueprint $t) {
                $t->id();
                $t->foreignId('site_id')->constrained('seo_sites')->cascadeOnDelete();
                $t->foreignId('keyword_id')->nullable()->constrained('seo_keywords')->nullOnDelete();
                $t->string('playbook_key', 80)->index();
                $t->string('dedupe_key', 191); // playbook_key|keyword|period — جلوگیری از تسک تکراری
                $t->string('pillar', 20); // infrastructure | goals
                $t->string('kind', 20)->default('audit'); // audit | keyword | recurring | opportunity
                $t->string('category', 30)->nullable();
                $t->string('title');
                $t->text('why')->nullable();
                $t->string('frequency', 20)->default('once'); // once | daily | weekly | monthly | quarterly
                $t->string('automation', 20)->default('auto'); // auto | assisted | manual
                $t->string('check', 80)->nullable();
                $t->unsignedTinyInteger('impact')->default(3);
                $t->unsignedTinyInteger('effort')->default(2);
                $t->smallInteger('priority')->default(0);
                $t->string('status', 30)->default('todo'); // todo | in_progress | needs_action | waiting_approval | done | skipped | failed
                $t->date('due_on')->nullable();
                $t->string('period_key', 20)->nullable(); // برای تکراری‌ها: 2026-W41 / 2026-10
                $t->json('result')->nullable();
                $t->text('last_message')->nullable();
                $t->unsignedInteger('attempts')->default(0);
                $t->timestamp('last_checked_at')->nullable();
                $t->timestamp('completed_at')->nullable();
                $t->string('completed_by')->nullable(); // agent | admin:ID | telegram:ID
                $t->timestamps();
                $t->index(['site_id', 'status']);
                $t->unique(['site_id', 'dedupe_key'], 'seo_tasks_unique');
            });
        }

        if (! Schema::hasTable('seo_scenarios')) {
            Schema::create('seo_scenarios', function (Blueprint $t) {
                $t->id();
                $t->foreignId('site_id')->constrained('seo_sites')->cascadeOnDelete();
                $t->string('key', 60);
                $t->string('title');
                $t->text('why')->nullable();
                $t->string('pillar', 20)->default('goals');
                $t->string('handler', 60);
                $t->string('frequency', 20)->default('daily');
                $t->string('at', 5)->default('06:00');
                $t->unsignedTinyInteger('weekday')->nullable();
                $t->unsignedTinyInteger('day')->nullable();
                $t->json('config')->nullable();
                $t->boolean('is_enabled')->default(true);
                $t->boolean('follow_profile')->default(true);
                $t->timestamp('last_run_at')->nullable();
                $t->timestamp('next_run_at')->nullable();
                $t->string('last_status', 20)->nullable();
                $t->text('last_summary')->nullable();
                $t->unsignedInteger('run_count')->default(0);
                $t->unsignedInteger('fail_count')->default(0);
                $t->timestamps();
                $t->unique(['site_id', 'key']);
            });
        }

        if (! Schema::hasTable('seo_runs')) {
            Schema::create('seo_runs', function (Blueprint $t) {
                $t->id();
                $t->foreignId('site_id')->constrained('seo_sites')->cascadeOnDelete();
                $t->foreignId('scenario_id')->nullable()->constrained('seo_scenarios')->nullOnDelete();
                $t->foreignId('task_id')->nullable()->constrained('seo_tasks')->nullOnDelete();
                $t->string('agent', 40);
                $t->string('action', 80);
                $t->string('status', 20)->default('running'); // running | success | warning | failed | skipped
                $t->string('trigger', 20)->default('schedule'); // schedule | manual | telegram | system
                $t->text('summary')->nullable();
                $t->json('output')->nullable();
                $t->decimal('cost_usd', 10, 5)->default(0);
                $t->unsignedInteger('duration_ms')->nullable();
                $t->timestamp('started_at')->nullable();
                $t->timestamp('finished_at')->nullable();
                $t->timestamps();
                $t->index(['site_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('seo_ai_calls')) {
            Schema::create('seo_ai_calls', function (Blueprint $t) {
                $t->id();
                $t->foreignId('site_id')->constrained('seo_sites')->cascadeOnDelete();
                $t->foreignId('run_id')->nullable()->constrained('seo_runs')->nullOnDelete();
                $t->string('role', 20);
                $t->string('purpose', 80);
                $t->string('model', 120);
                $t->boolean('web_search')->default(false);
                $t->unsignedInteger('tokens_in')->default(0);
                $t->unsignedInteger('tokens_out')->default(0);
                $t->decimal('cost_usd', 10, 6)->default(0);
                $t->boolean('cost_estimated')->default(false);
                $t->boolean('ok')->default(true);
                $t->text('error')->nullable();
                $t->unsignedInteger('duration_ms')->nullable();
                $t->timestamps();
                $t->index(['site_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('seo_content_items')) {
            Schema::create('seo_content_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('site_id')->constrained('seo_sites')->cascadeOnDelete();
                $t->foreignId('keyword_id')->nullable()->constrained('seo_keywords')->nullOnDelete();
                $t->string('type', 20)->default('article'); // article | refresh | page_update
                $t->string('status', 30)->default('idea'); // idea | brief | drafting | review | approved | published | rejected | failed
                $t->string('title')->nullable();
                $t->string('slug')->nullable();
                $t->string('target_url')->nullable();
                $t->json('brief')->nullable();
                $t->json('blocks')->nullable(); // بلوک‌های سازگار با ArticleContentService
                $t->string('meta_title')->nullable();
                $t->string('meta_description', 300)->nullable();
                $t->json('faq')->nullable();
                $t->unsignedTinyInteger('seo_score')->nullable();
                $t->json('quality')->nullable();
                $t->unsignedInteger('word_count')->default(0);
                $t->decimal('cost_usd', 10, 5)->default(0);
                $t->string('published_ref')->nullable(); // شناسه در سیستم مقصد
                $t->string('published_url')->nullable();
                $t->timestamp('published_at')->nullable();
                $t->string('approved_by')->nullable();
                $t->timestamp('approved_at')->nullable();
                $t->text('reviewer_note')->nullable();
                $t->date('planned_for')->nullable();
                $t->timestamps();
                $t->index(['site_id', 'status']);
            });
        }

        if (! Schema::hasTable('seo_audits')) {
            Schema::create('seo_audits', function (Blueprint $t) {
                $t->id();
                $t->foreignId('site_id')->constrained('seo_sites')->cascadeOnDelete();
                $t->string('type', 30); // crawl | pagespeed | health
                $t->unsignedTinyInteger('score')->nullable();
                $t->json('summary')->nullable();
                $t->json('issues')->nullable();
                $t->json('pages')->nullable();
                $t->timestamps();
                $t->index(['site_id', 'type', 'created_at']);
            });
        }

        if (! Schema::hasTable('seo_alerts')) {
            Schema::create('seo_alerts', function (Blueprint $t) {
                $t->id();
                $t->foreignId('site_id')->constrained('seo_sites')->cascadeOnDelete();
                $t->string('level', 20)->default('info'); // info | success | warning | danger
                $t->string('type', 40);
                $t->string('title');
                $t->text('body')->nullable();
                $t->json('data')->nullable();
                $t->timestamp('telegram_sent_at')->nullable();
                $t->timestamp('read_at')->nullable();
                $t->timestamps();
                $t->index(['site_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('seo_telegram_admins')) {
            Schema::create('seo_telegram_admins', function (Blueprint $t) {
                $t->id();
                $t->string('chat_id', 40)->unique();
                $t->string('name')->nullable();
                $t->string('username')->nullable();
                $t->unsignedBigInteger('admin_id')->nullable();
                $t->boolean('is_active')->default(true);
                $t->json('preferences')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'seo_telegram_admins', 'seo_alerts', 'seo_audits', 'seo_content_items', 'seo_ai_calls', 'seo_runs',
            'seo_scenarios', 'seo_tasks', 'seo_query_metrics', 'seo_daily_metrics', 'seo_keyword_ranks',
            'seo_keywords', 'seo_keyword_clusters', 'seo_sites',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
