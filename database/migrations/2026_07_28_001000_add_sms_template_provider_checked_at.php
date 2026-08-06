<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('sms_templates', 'provider_checked_at')) {
            Schema::table('sms_templates', function (Blueprint $table) {
                $table->timestamp('provider_checked_at')->nullable()->after('provider_submitted_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sms_templates', 'provider_checked_at')) {
            Schema::table('sms_templates', function (Blueprint $table) {
                $table->dropColumn('provider_checked_at');
            });
        }
    }
};
