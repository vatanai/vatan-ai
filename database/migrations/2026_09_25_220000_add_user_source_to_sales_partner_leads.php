<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_partner_leads', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->string('source', 40)->default('manual')->after('profile_url');
            $table->index(['user_id', 'status'], 'spl_user_status_idx');
            $table->index('source', 'spl_source_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sales_partner_leads', function (Blueprint $table): void {
            $table->dropIndex('spl_user_status_idx');
            $table->dropIndex('spl_source_idx');
            $table->dropColumn(['user_id', 'source']);
        });
    }
};
