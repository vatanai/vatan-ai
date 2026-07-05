<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // اگر فیلد tokens از قبل وجود دارد، خط زیر را بنویسید در غیر این صورت آن را هم اضافه کنید
            if (!Schema::hasColumn('users', 'tokens')) {
                $table->integer('tokens')->default(0)->after('password');
            }
            $table->integer('tokens_purchased')->default(0)->after('tokens');
            $table->integer('tokens_used')->default(0)->after('tokens_purchased');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tokens_purchased', 'tokens_used']);
        });
    }
};