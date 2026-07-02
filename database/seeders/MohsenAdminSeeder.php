<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MohsenAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'mohsen@vatanai.com'],
            [
                'name'     => 'mohsen',
                'password' => Hash::make('11111'),
            ]
        );
    }
}
