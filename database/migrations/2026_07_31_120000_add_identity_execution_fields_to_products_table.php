<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('identity_model')->nullable()->after('identity_instructions');
            $table->string('identity_model_provider')->nullable()->after('identity_model');
            $table->unsignedInteger('identity_credit_cost')->default(0)->after('identity_model_provider');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['identity_model', 'identity_model_provider', 'identity_credit_cost']);
        });
    }
};
