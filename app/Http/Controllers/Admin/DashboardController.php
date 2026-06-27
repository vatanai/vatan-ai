<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Generation;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users_count'       => User::count(),
            'generations_count' => Generation::count(),
            'products_count'    => Product::count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
