<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class SavedProductController extends Controller
{
    /**
     * سیو/حذف سیو یک محصول برای کاربر لاگین‌کرده (Toggle).
     * میدل‌ور auth مسیر تضمین می‌کند کاربر مهمان اصلاً به این متد نمی‌رسد
     * (برای درخواست‌های Ajax، پاسخ خودکار 401 JSON برمی‌گردد).
     */
    public function toggle(Request $request, Product $product)
    {
        $user = $request->user();

        $existing = $user->savedProducts()->where('product_id', $product->id)->first();

        if ($existing) {
            $user->savedProducts()->detach($product->id);
            $saved = false;
        } else {
            $user->savedProducts()->attach($product->id);
            $saved = true;
        }

        return response()->json([
            'success' => true,
            'saved'   => $saved,
        ]);
    }
}
