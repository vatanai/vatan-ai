<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductDownload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductDownloadController extends Controller
{
    public function store(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->status === 'active', 404);

        ProductDownload::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['success' => true]);
    }
}
