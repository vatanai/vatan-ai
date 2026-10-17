<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seo_telegram_admins')) {
            Schema::table('seo_telegram_admins', function (Blueprint $table) {
                if (! Schema::hasColumn('seo_telegram_admins', 'telegram_user_id')) {
                    $table->unsignedBigInteger('telegram_user_id')->nullable()->after('username');
                }
                if (! Schema::hasColumn('seo_telegram_admins', 'message_thread_id')) {
                    $table->unsignedBigInteger('message_thread_id')->nullable()->after('telegram_user_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('seo_telegram_admins')) {
            Schema::table('seo_telegram_admins', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('seo_telegram_admins', 'telegram_user_id')) {
                    $columns[] = 'telegram_user_id';
                }
                if (Schema::hasColumn('seo_telegram_admins', 'message_thread_id')) {
                    $columns[] = 'message_thread_id';
                }
                if ($columns) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
