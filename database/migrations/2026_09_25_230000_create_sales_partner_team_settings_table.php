<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_partner_team_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('admin_id')->unique('spts_admin_unique');
            $table->unsignedSmallInteger('daily_contact_target')->default(20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_partner_team_settings');
    }
};
