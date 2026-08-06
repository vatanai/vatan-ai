<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->string('phone', 11)->nullable()->unique()->after('email');
            $table->boolean('is_active')->default(true)->index()->after('role');
        });

        DB::table('admins')->where('email', 'admin@1')->delete();

        $amir = DB::table('admins')->where('email', 'amirtojar86@gmail.com')->first();
        if ($amir) {
            DB::table('admins')->where('id', $amir->id)->update([
                'role' => 'leader',
                'is_active' => true,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('admins')->insert([
                'name' => 'امیر',
                'email' => 'amirtojar86@gmail.com',
                'phone' => null,
                'role' => 'leader',
                'is_active' => true,
                'password' => Hash::make('amir25191mk'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['phone', 'is_active']);
        });
    }
};
