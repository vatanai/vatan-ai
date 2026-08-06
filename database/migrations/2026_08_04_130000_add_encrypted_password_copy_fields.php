<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['admins', 'users'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'password_reveal')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->text('password_reveal')->nullable()->after('password');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['admins', 'users'] as $tableName) {
            if (Schema::hasColumn($tableName, 'password_reveal')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('password_reveal');
                });
            }
        }
    }
};
