<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** بات تلگرام «ثبت پست»: فهرست مجاز تلگرام (کارمندان/ادمین‌ها) + نشانه‌ی اطلاع‌رسانی پست تازه. */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('instagram_telegram_admins')) {
            Schema::create('instagram_telegram_admins', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->index();
                // ادمین پنل (اختیاری) — کارمندی که فقط با شناسه‌ی تلگرام اضافه شده، ادمین ندارد.
                $table->foreignId('admin_id')->nullable()->index();
                $table->unsignedBigInteger('telegram_id')->unique();
                $table->string('name', 120)->nullable();
                $table->string('role', 20)->default('manager'); // manager: تنظیم و فعال‌سازی · viewer: فقط مشاهده
                $table->foreignId('added_by')->nullable();
                $table->string('username', 64)->nullable();
                $table->string('first_name', 120)->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('notify_new_posts')->default(true);
                $table->timestamp('linked_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('instagram_posts') && !Schema::hasColumn('instagram_posts', 'telegram_notified_at')) {
            Schema::table('instagram_posts', function (Blueprint $table) {
                $table->timestamp('telegram_notified_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('instagram_posts', 'telegram_notified_at')) {
            Schema::table('instagram_posts', fn (Blueprint $table) => $table->dropColumn('telegram_notified_at'));
        }
        Schema::dropIfExists('instagram_telegram_admins');
    }
};
