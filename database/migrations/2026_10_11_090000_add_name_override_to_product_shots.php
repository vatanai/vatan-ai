<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_shots') && ! Schema::hasColumn('product_shots', 'name_override')) {
            Schema::table('product_shots', function (Blueprint $table): void {
                $table->string('name_override', 120)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_shots') && Schema::hasColumn('product_shots', 'name_override')) {
            Schema::table('product_shots', function (Blueprint $table): void {
                $table->dropColumn('name_override');
            });
        }
    }
};
