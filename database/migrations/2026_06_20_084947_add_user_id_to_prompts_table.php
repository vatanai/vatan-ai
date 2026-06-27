<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prompts', function (Blueprint $table) {
            // اضافه کردن ستون user_id و اتصال آن به جدول users
            // از nullable استفاده می‌کنیم تا اگر از قبل دیتایی دارید ارور ندهد
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prompts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};