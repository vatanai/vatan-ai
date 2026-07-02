<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'amirtojar86@gmail.com'],
            [
                'name' => 'Amir Admin',
                'password' => Hash::make('amir25191mk'),
            ]
        );

        Admin::updateOrCreate(
            ['email' => 'admiinn@aivatan.com'],
            [
                'name' => 'Mohsen Admin',
                'password' => Hash::make('11'),
            ]
        );
    }
}