<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جدول هماهنگ‌سازی تماس‌ها برای Telegram/Instagram
 * برای Phase 4: دوطرفه‌سازی کامل Dashboard ↔ Telegram ↔ Instagram
 */
return new class extends Migration
{
    public function up(): void
    {
        // جدول جدید: instagram_contact_integrations
        if (!Schema::hasTable('instagram_contact_integrations')) {
            Schema::create('instagram_contact_integrations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contact_id')->constrained('instagram_contacts')->cascadeOnDelete();
                $table->unsignedBigInteger('telegram_user_id')->nullable()->index();
                $table->unsignedBigInteger('telegram_topic_id')->nullable()->index();
                $table->string('telegram_topic_name', 255)->nullable();
                $table->enum('sync_direction', ['inbound', 'outbound', 'both'])->default('both');
                $table->timestamp('last_sync_at')->nullable();
                $table->timestamps();
                $table->unique(['contact_id', 'telegram_user_id'], 'ig_contact_integration_unique');
            });
        }

        // افزایش ستون‌های جدید به instagram_messages
        if (Schema::hasTable('instagram_messages') && !Schema::hasColumn('instagram_messages', 'telegram_message_id')) {
            Schema::table('instagram_messages', function (Blueprint $table) {
                $table->unsignedBigInteger('telegram_message_id')->nullable()->index();
                $table->timestamp('telegram_forwarded_at')->nullable();
                $table->boolean('forwarded_from_dashboard')->default(false);
            });
        }

        // افزایش ستون‌های جدید به instagram_contacts
        if (Schema::hasTable('instagram_contacts') && !Schema::hasColumn('instagram_contacts', 'telegram_user_id')) {
            Schema::table('instagram_contacts', function (Blueprint $table) {
                $table->unsignedBigInteger('telegram_user_id')->nullable()->index();
                $table->unsignedBigInteger('telegram_topic_id')->nullable()->index();
                $table->boolean('consent_telegram')->default(false);
            });
        }
    }

    public function down(): void
    {
        // برگشت تغییرات instagram_contacts
        if (Schema::hasTable('instagram_contacts')) {
            Schema::table('instagram_contacts', function (Blueprint $table) {
                $table->dropIndexIfExists('ig_contact_telegram_user_idx');
                $table->dropIndexIfExists('ig_contact_telegram_topic_idx');
                $table->dropColumn(['telegram_user_id', 'telegram_topic_id', 'consent_telegram']);
            });
        }

        // برگشت تغییرات instagram_messages
        if (Schema::hasTable('instagram_messages')) {
            Schema::table('instagram_messages', function (Blueprint $table) {
                $table->dropIndexIfExists('ig_message_telegram_msg_idx');
                $table->dropColumn(['telegram_message_id', 'telegram_forwarded_at', 'forwarded_from_dashboard']);
            });
        }

        // حذف جدول جدید
        Schema::dropIfExists('instagram_contact_integrations');
    }
};
