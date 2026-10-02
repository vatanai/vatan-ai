<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * «اینستاگرام هوشمند» — هسته‌ی داده (پروپوزال doc/instagram-smart-proposal.md، بخش ۷).
 *
 * - همه‌ی جدول‌ها از ابتدا workspace_id دارند تا عرضه‌ی اشتراکی نیازمند بازنویسی نباشد.
 * - اعتبارنامه‌ی متا در همان marketing_integrations (رمزنگاری‌شده) می‌ماند؛ این‌جا فقط ارجاع داریم.
 * - marketing_events همچنان دفتر خام رویدادهاست؛ این جدول‌ها نتیجه‌ی نرمال‌سازی آن هستند.
 * - مایگریشن idempotent است و به هیچ جدول موجود دیگری دست نمی‌زند.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->create('instagram_workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 80)->unique();
            $table->string('status', 20)->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        $this->create('instagram_workspace_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('role', 30)->default('operator');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['workspace_id', 'admin_id'], 'ig_member_unique');
        });

        $this->create('instagram_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->foreignId('marketing_integration_id')->nullable()->constrained('marketing_integrations')->nullOnDelete();
            $table->string('gateway', 30)->default('meta');
            $table->string('name');
            $table->string('external_account_id', 100)->nullable()->index();
            $table->string('username', 120)->nullable();
            $table->string('account_type', 30)->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->boolean('outbound_enabled')->default(false);
            $table->timestamp('last_event_at')->nullable();
            $table->timestamp('health_checked_at')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        $this->create('instagram_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->string('external_id', 100)->nullable();
            $table->string('username', 120)->nullable()->index();
            $table->string('display_name')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('industry', 60)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('language', 10)->default('fa');
            $table->string('first_source', 30)->nullable();
            $table->string('first_source_ref', 120)->nullable();
            $table->string('lead_status', 30)->default('new')->index();
            $table->unsignedTinyInteger('lead_score')->default(0)->index();
            $table->string('score_reason')->nullable();
            $table->json('interests')->nullable();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->boolean('consent_contact')->default(false);
            $table->boolean('opted_out')->default(false);
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamp('last_interaction_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['workspace_id', 'external_id'], 'ig_contact_external_unique');
        });

        $this->create('instagram_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained('instagram_channels')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('instagram_contacts')->cascadeOnDelete();
            $table->string('status', 30)->default('new')->index();
            $table->string('priority', 20)->default('normal');
            $table->string('last_source', 30)->nullable();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->boolean('ai_paused')->default(false);
            $table->boolean('needs_human')->default(false)->index();
            $table->unsignedInteger('unread_count')->default(0);
            $table->string('last_message_preview', 300)->nullable();
            $table->string('last_message_direction', 10)->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('last_outbound_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->unsignedInteger('first_response_seconds')->nullable();
            $table->string('intent', 40)->nullable()->index();
            $table->string('stage', 40)->nullable();
            $table->string('urgency', 20)->nullable();
            $table->text('summary')->nullable();
            $table->text('next_action')->nullable();
            $table->timestamp('summary_updated_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->unique(['channel_id', 'contact_id'], 'ig_conversation_unique');
            $table->index(['workspace_id', 'status', 'last_message_at'], 'ig_conv_inbox_idx');
            $table->index(['workspace_id', 'last_inbound_at'], 'ig_conv_inbound_idx');
        });

        $this->create('instagram_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('instagram_conversations')->cascadeOnDelete();
            $table->string('external_id', 191)->nullable();
            $table->string('direction', 10);
            $table->string('source_type', 30)->default('dm');
            $table->string('source_ref', 120)->nullable()->index();
            $table->string('parent_external_id', 191)->nullable();
            $table->string('message_type', 20)->default('text');
            $table->text('body')->nullable();
            $table->string('sent_by', 20)->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->boolean('is_internal_note')->default(false);
            $table->string('delivery_status', 20)->nullable();
            $table->unsignedBigInteger('marketing_event_id')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
            $table->unique(['workspace_id', 'external_id'], 'ig_message_external_unique');
            $table->index(['conversation_id', 'occurred_at'], 'ig_msg_timeline_idx');
            $table->index(['workspace_id', 'direction', 'occurred_at'], 'ig_msg_report_idx');
        });

        $this->create('instagram_message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->foreignId('message_id')->constrained('instagram_messages')->cascadeOnDelete();
            $table->string('type', 20);
            $table->text('remote_url')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('fetch_status', 20)->default('pending')->index();
            $table->unsignedTinyInteger('fetch_attempts')->default(0);
            $table->text('fetch_error')->nullable();
            $table->text('transcript')->nullable();
            $table->string('transcript_language', 10)->nullable();
            $table->decimal('transcript_confidence', 4, 3)->nullable();
            $table->string('transcript_status', 20)->nullable();
            $table->timestamps();
        });

        $this->create('instagram_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('tone', 20)->default('neutral');
            $table->timestamps();
            $table->unique(['workspace_id', 'name'], 'ig_tag_unique');
        });

        $this->create('instagram_contact_tag', function (Blueprint $table) {
            $table->foreignId('contact_id')->constrained('instagram_contacts')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('instagram_tags')->cascadeOnDelete();
            $table->primary(['contact_id', 'tag_id']);
        });

        $this->create('instagram_contact_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('instagram_contacts')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        $this->create('instagram_deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('instagram_contacts')->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('instagram_conversations')->nullOnDelete();
            $table->string('title');
            $table->string('stage', 40)->default('new')->index();
            $table->unsignedBigInteger('value_toman')->default(0);
            $table->string('outcome', 20)->default('open')->index();
            $table->string('lost_reason')->nullable();
            $table->string('source_type', 30)->nullable();
            $table->string('source_ref', 120)->nullable()->index();
            $table->foreignId('owner_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('stage_changed_at')->nullable();
            $table->timestamp('closed_at')->nullable()->index();
            $table->timestamps();
        });

        $this->create('instagram_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('instagram_contacts')->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('instagram_conversations')->nullOnDelete();
            $table->foreignId('deal_id')->nullable()->constrained('instagram_deals')->nullOnDelete();
            $table->string('type', 30)->default('follow_up');
            $table->string('title');
            $table->timestamp('due_at')->nullable()->index();
            $table->string('status', 20)->default('open')->index();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('result')->nullable();
            $table->string('created_via', 20)->default('human');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        $this->create('instagram_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->foreignId('channel_id')->nullable()->constrained('instagram_channels')->nullOnDelete();
            $table->string('name');
            $table->string('trigger', 40)->index();
            $table->string('scope_ref', 120)->nullable();
            $table->json('keywords')->nullable();
            $table->string('match_mode', 20)->default('contains');
            $table->json('conditions')->nullable();
            $table->json('actions');
            $table->json('guards')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->unsignedInteger('runs_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->text('last_error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        $this->create('instagram_automation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->foreignId('rule_id')->constrained('instagram_automation_rules')->cascadeOnDelete();
            $table->unsignedInteger('rule_version');
            $table->foreignId('message_id')->nullable()->constrained('instagram_messages')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('instagram_contacts')->nullOnDelete();
            $table->string('mode', 20)->default('live');
            $table->string('status', 20)->index();
            $table->json('decisions')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->unique(['rule_id', 'message_id'], 'ig_run_once_per_message');
            $table->index(['rule_id', 'contact_id', 'created_at'], 'ig_run_guard_idx');
        });

        $this->create('instagram_outbound_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained('instagram_channels')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('instagram_conversations')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('instagram_contacts')->cascadeOnDelete();
            $table->string('kind', 30)->default('dm');
            $table->string('target_ref', 191)->nullable();
            $table->text('body');
            $table->string('origin', 20);
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->unsignedBigInteger('automation_run_id')->nullable()->index();
            $table->unsignedBigInteger('ai_suggestion_id')->nullable()->index();
            $table->string('status', 20)->default('pending')->index();
            $table->string('policy_reason')->nullable();
            $table->timestamp('window_expires_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->string('external_id', 191)->nullable();
            $table->text('error')->nullable();
            $table->json('provider_response')->nullable();
            $table->string('idempotency_key', 191)->unique();
            $table->unsignedBigInteger('message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['contact_id', 'origin', 'created_at'], 'ig_out_rate_idx');
        });

        $this->create('instagram_ai_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->boolean('is_active')->default(false)->index();
            $table->string('assistant_name', 80)->nullable();
            $table->text('persona_prompt');
            $table->string('tone', 40)->default('friendly');
            $table->string('reply_length', 20)->default('short');
            $table->string('bot_disclosure', 20)->default('when_asked');
            $table->json('forbidden_phrases')->nullable();
            $table->json('escalation_keywords')->nullable();
            $table->decimal('min_confidence', 3, 2)->default(0.65);
            $table->string('model', 120)->nullable();
            $table->string('reply_mode', 20)->default('suggest');
            $table->text('change_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
            $table->unique(['workspace_id', 'version'], 'ig_ai_profile_version');
        });

        $this->create('instagram_knowledge_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->string('title');
            $table->string('category', 40)->default('general')->index();
            $table->string('source_type', 20)->default('text');
            $table->string('original_filename')->nullable();
            $table->string('storage_path')->nullable();
            $table->longText('content');
            $table->string('content_hash', 64)->nullable();
            $table->unsignedInteger('char_count')->default(0);
            $table->unsignedInteger('chunk_count')->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('ai_allowed')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->date('valid_until')->nullable();
            $table->json('digest')->nullable();
            $table->string('digest_status', 20)->nullable();
            $table->timestamp('digested_at')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        $this->create('instagram_knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->foreignId('source_id')->constrained('instagram_knowledge_sources')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->text('content');
            $table->text('search_text');
            $table->timestamps();
            $table->index(['workspace_id', 'source_id'], 'ig_chunk_source_idx');
        });

        $this->create('instagram_ai_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('instagram_conversations')->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('instagram_messages')->nullOnDelete();
            $table->string('purpose', 30);
            $table->string('model', 120)->nullable();
            $table->unsignedInteger('profile_version')->nullable();
            $table->string('status', 20)->index();
            $table->json('input_summary')->nullable();
            $table->json('output')->nullable();
            $table->decimal('confidence', 4, 3)->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->decimal('cost_usd', 12, 6)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        $this->create('instagram_ai_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('instagram_conversations')->cascadeOnDelete();
            $table->foreignId('ai_run_id')->nullable()->constrained('instagram_ai_runs')->nullOnDelete();
            $table->text('body');
            $table->text('reason')->nullable();
            $table->json('source_ids')->nullable();
            $table->decimal('confidence', 4, 3)->nullable();
            $table->boolean('needs_human')->default(false);
            $table->json('flags')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('final_body')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('feedback', 300)->nullable();
            $table->timestamps();
        });

        $this->create('instagram_operation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained('instagram_workspaces')->cascadeOnDelete();
            $table->string('action', 60)->index();
            $table->string('level', 10)->default('info')->index();
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('message', 500);
            $table->json('context')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        $this->seedDefaults();
    }

    public function down(): void
    {
        foreach ([
            'instagram_operation_logs', 'instagram_ai_suggestions', 'instagram_ai_runs', 'instagram_knowledge_chunks',
            'instagram_knowledge_sources', 'instagram_ai_profiles', 'instagram_outbound_messages', 'instagram_automation_runs',
            'instagram_automation_rules', 'instagram_tasks', 'instagram_deals', 'instagram_contact_notes', 'instagram_contact_tag',
            'instagram_tags', 'instagram_message_attachments', 'instagram_messages', 'instagram_conversations', 'instagram_contacts',
            'instagram_channels', 'instagram_workspace_members', 'instagram_workspaces',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function create(string $table, \Closure $callback): void
    {
        if (!Schema::hasTable($table)) {
            Schema::create($table, $callback);
        }
    }

    /** فضای کاری وطن + پروفایل گفتمان پیش‌فرض (فقط اگر وجود ندارند). */
    private function seedDefaults(): void
    {
        $now = now();
        if (!DB::table('instagram_workspaces')->where('slug', 'vatan')->exists()) {
            DB::table('instagram_workspaces')->insert([
                'name' => 'وطن',
                'slug' => 'vatan',
                'status' => 'active',
                'settings' => json_encode(['business_hours' => ['start' => '09:00', 'end' => '21:00'], 'timezone' => 'Asia/Tehran']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $workspaceId = (int) DB::table('instagram_workspaces')->where('slug', 'vatan')->value('id');
        if (!DB::table('instagram_ai_profiles')->where('workspace_id', $workspaceId)->exists()) {
            DB::table('instagram_ai_profiles')->insert([
                'workspace_id' => $workspaceId,
                'version' => 1,
                'is_active' => true,
                'assistant_name' => 'دستیار فروش وطن',
                'persona_prompt' => "تو دستیار فروش وطن هستی؛ سرویسی که برای کسب‌وکارها عکس و ویدیوی محصول با هوش مصنوعی می‌سازد.\n"
                    ."- کوتاه، گرم و محترمانه و به فارسی محاوره‌ی مؤدب پاسخ بده.\n"
                    ."- فقط از اطلاعات «دانش تأییدشده» استفاده کن؛ قیمت، تخفیف، زمان تحویل و موجودی را هرگز حدس نزن.\n"
                    ."- اگر اطلاعات کافی نداری، یک سؤال کوتاه برای روشن‌شدن نیاز مشتری بپرس یا بگو همکار انسانی پاسخ می‌دهد.\n"
                    ."- هدف: فهمیدن صنف و نیاز مشتری و هدایت او به ثبت محصول یا مشاوره.",
                'tone' => 'friendly',
                'reply_length' => 'short',
                'bot_disclosure' => 'when_asked',
                'forbidden_phrases' => json_encode(['تضمینی', '۱۰۰٪ رایگان']),
                'escalation_keywords' => json_encode(['شکایت', 'کلاهبرداری', 'پس دادن پول', 'بازگشت وجه', 'وکیل', 'شکایت قانونی', 'پرداخت نشد', 'پولم', 'همکاری']),
                'min_confidence' => 0.65,
                'model' => null,
                'reply_mode' => 'suggest',
                'change_note' => 'نسخه‌ی اولیه',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
