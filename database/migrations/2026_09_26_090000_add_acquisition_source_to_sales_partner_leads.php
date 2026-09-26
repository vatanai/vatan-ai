<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sales_partner_leads', 'acquisition_source')) {
            Schema::table('sales_partner_leads', function (Blueprint $table): void {
                $table->string('acquisition_source', 40)->default('manual')->after('source');
                $table->index('acquisition_source', 'spl_acquisition_source_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales_partner_leads', 'acquisition_source')) {
            Schema::table('sales_partner_leads', function (Blueprint $table): void {
                $table->dropIndex('spl_acquisition_source_idx');
                $table->dropColumn('acquisition_source');
            });
        }
    }
};
