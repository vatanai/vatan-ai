<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            $table->string('last_test_status', 20)->nullable()->after('last_sent_at');
            $table->timestamp('last_tested_at')->nullable()->after('last_test_status');
        });
    }

    public function down(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            $table->dropColumn(['last_test_status', 'last_tested_at']);
        });
    }
};
