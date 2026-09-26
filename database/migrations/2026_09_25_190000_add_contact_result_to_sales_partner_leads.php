<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_partner_leads', function (Blueprint $table): void {
            $table->string('last_contact_result', 30)->nullable()->after('last_contact_type');
        });
    }

    public function down(): void
    {
        Schema::table('sales_partner_leads', function (Blueprint $table): void {
            $table->dropColumn('last_contact_result');
        });
    }
};
