<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('admins')
            ->where('email', 'amirtojar86@gmail.com')
            ->delete();

        $admins = [
            [
                'name' => 'مجتبی حسنلو',
                'email' => 'mojtaba@vatan.ai',
                'password' => '$2y$12$TtOoMgWHmkvqW43dm5sDMO6uG4/aU1cJoBO/39NPxo78TlpcPdPfa',
            ],
            [
                'name' => 'ساغر محمدی',
                'email' => 'saghar@vatan.ai',
                'password' => '$2y$12$lmeCwnbxuTnr8bcV.iWmi.PESvXJakDp.JtYV5AECFS2RMAenEdFe',
            ],
        ];

        foreach ($admins as $admin) {
            DB::table('admins')->updateOrInsert(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'phone' => null,
                    'role' => 'leader',
                    'is_active' => true,
                    'password' => $admin['password'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('admins')
            ->whereIn('email', ['mojtaba@vatan.ai', 'saghar@vatan.ai'])
            ->delete();
    }
};
