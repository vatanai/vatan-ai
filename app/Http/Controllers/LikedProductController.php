<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class LikedProductController extends Controller
{
    /**
     * لایک/حذف لایک یک محصول برای کاربر لاگین‌کرده (Toggle).
     * میدل‌ور auth مسیر تضمین می‌کند کاربر مهمان به این متد نمی‌رسد
     * (برای درخواست‌های Ajax، پاسخ خودکار 401 JSON برمی‌گردد).
     */
    public function toggle(Request $request, Product $product)
    {
        $user = $request->user();

        $existing = $user->likedProducts()->where('product_id', $product->id)->first();

        if ($existing) {
            $user->likedProducts()->detach($product->id);
            $liked = false;
        } else {
            $user->likedProducts()->attach($product->id);
            $liked = true;
        }

        return response()->json([
            'success' => true,
            'liked'   => $liked,
        ]);
    }
}
