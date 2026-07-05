<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /**
     * نمایش لیست تمام کاربران سیستم (بدون صفحه‌بندی) به همراه محصولات استفاده شده
     */
    public function index()
    {
        // لود کردن تصاویر خلق شده به همراه محصول مربوط به هر تصویر برای نمایش در مودال
        $users = User::with(['generatedImages.product'])
            ->withCount('generatedImages')
            ->latest()
            ->get();

        return view('admin.users.index', compact('users'));
    }

    /**
     * تغییر وضعیت کاربر به صورت آنلاین (Ajax)
     */
   public function changeStatus(Request $request, $id)
{
    $request->validate([
        'status' => 'required|in:active,suspended,deleted'
    ]);

    $user = User::find($id);
    
    if (!$user) {
        return response()->json(['status' => 'error', 'message' => 'کاربر یافت نشد.'], 404);
    }

    $user->status = $request->status;
    $user->save();

    return response()->json(['status' => 'success', 'message' => 'وضعیت کاربر با موفقیت بروزرسانی شد.']);
}
}