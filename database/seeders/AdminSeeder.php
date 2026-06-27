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
            ['email' => 'amirtojar86@gmail.com'], // بررسی برای عدم ایجاد رکورد تکراری
            [
                'name' => 'Amir Admin',
                'password' => Hash::make('amir25191mk') // هش کردن امن پسورد شما
            ]
        );
    }
}