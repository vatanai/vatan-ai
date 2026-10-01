<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Posts configuration table
        Schema::create('instagram_post_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Post Information
            $table->string('title')->comment('عنوان پست برای مرجع');
            $table->string('instagram_post_id')->unique()->comment('Instagram Media ID');
            $table->text('instagram_caption')->nullable()->comment('متن اصلی پست');
            
            // Status and Timeline
            $table->enum('status', ['active', 'inactive', 'testing'])->default('testing');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            
            // Follower Requirements
            $table->boolean('require_follow')->default(true)->comment('آیا فالو اجباری است');
            $table->integer('min_followers')->default(0)->comment('حداقل تعداد فالوورهای کاربر');
            
            // Response Configuration (JSON)
            $table->json('non_follower_response')->comment('پاسخ برای غیر فالوها');
            $table->json('follower_response')->comment('پاسخ برای فالوهایی');
            
            // Delays (in seconds)
            $table->integer('comment_reply_delay')->default(3);
            $table->integer('dm_product_delay')->default(5);
            $table->integer('dm_form_delay')->default(2);
            
            // Repeat policy
            $table->enum('repeat_policy', ['once_per_user', 'every_time', 'once_per_day'])->default('once_per_user');
            
            // Logging and Notifications
            $table->boolean('log_all_interactions')->default(true);
            $table->boolean('notify_slack')->default(false);
            $table->boolean('daily_excel_export')->default(false);
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['instagram_post_id']);
        });

        // Keywords/Triggers for each post
        Schema::create('instagram_post_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_setting_id')->constrained('instagram_post_settings')->cascadeOnDelete();
            
            $table->string('keyword');
            $table->enum('match_type', ['exact', 'contains', 'regex'])->default('exact');
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->unique(['post_setting_id', 'keyword']);
            $table->index(['post_setting_id']);
        });

        // Response templates for posts
        Schema::create('instagram_post_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_setting_id')->constrained('instagram_post_settings')->cascadeOnDelete();
            
            $table->enum('scenario', ['non_follower', 'follower'])->comment('سناریو: فالو نشده یا فالو شده');
            $table->enum('target', ['comment', 'dm_text', 'dm_product', 'dm_form'])->comment('تارگت: کامنت یا DM');
            
            $table->text('message')->comment('پیام متنی');
            $table->json('metadata')->nullable()->comment('اطلاعات اضافی');
            
            $table->timestamps();
            
            $table->index(['post_setting_id', 'scenario', 'target']);
        });

        // Products associated with posts
        Schema::create('instagram_post_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_setting_id')->constrained('instagram_post_settings')->cascadeOnDelete();
            
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 0)->nullable();
            $table->string('image_url')->nullable();
            $table->string('product_link')->nullable();
            $table->string('sku')->nullable();
            
            $table->integer('inventory')->default(0);
            $table->boolean('is_active')->default(true);
            
            // For product card display
            $table->text('custom_message')->nullable();
            
            $table->timestamps();
            
            $table->index(['post_setting_id']);
        });

        // Analytics and statistics for each post
        Schema::create('instagram_post_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_setting_id')->constrained('instagram_post_settings')->cascadeOnDelete();
            
            // Counts
            $table->integer('total_comments')->default(0);
            $table->integer('keyword_matched')->default(0);
            $table->integer('follow_required_users')->default(0);
            $table->integer('follow_completed')->default(0);
            $table->integer('dm_sent')->default(0);
            $table->integer('product_offered')->default(0);
            $table->integer('forms_completed')->default(0);
            $table->integer('conversions')->default(0);
            
            // Engagement rates
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->decimal('follow_completion_rate', 5, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            
            // Timestamps
            $table->date('date');
            $table->timestamps();
            
            $table->unique(['post_setting_id', 'date']);
            $table->index(['post_setting_id', 'date']);
        });

        // Audit log for each post's activities
        Schema::create('instagram_post_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_setting_id')->constrained('instagram_post_settings')->cascadeOnDelete();
            $table->foreignId('instagram_comment_id')->nullable()->constrained()->nullableOnDelete();
            
            $table->string('instagram_user_id')->comment('Instagram user ID');
            $table->string('instagram_username')->comment('Instagram username');
            
            $table->string('action')->comment('What happened: comment_matched, follow_required, dm_sent, form_submitted');
            $table->text('details')->nullable()->comment('JSON details about the action');
            
            $table->enum('status', ['success', 'failed', 'skipped'])->default('success');
            $table->string('failure_reason')->nullable();
            
            $table->timestamps();
            
            $table->index(['post_setting_id', 'created_at']);
            $table->index(['instagram_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_post_audit_logs');
        Schema::dropIfExists('instagram_post_analytics');
        Schema::dropIfExists('instagram_post_products');
        Schema::dropIfExists('instagram_post_responses');
        Schema::dropIfExists('instagram_post_keywords');
        Schema::dropIfExists('instagram_post_settings');
    }
};
