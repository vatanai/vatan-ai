<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;

class AdminSeeder extends Seeder
{
    public function run(): void
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
            Admin::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'role' => 'leader',
                    'is_active' => true,
                    'password' => $admin['password'],
                ]
            );
        }
    }
}
