<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('categories', 'cover_ratio')) {
            Schema::table('categories', function (Blueprint $table): void {
                $table->string('cover_ratio', 5)->default('1:1')->after('image');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('categories', 'cover_ratio')) {
            Schema::table('categories', function (Blueprint $table): void {
                $table->dropColumn('cover_ratio');
            });
        }
    }
};
