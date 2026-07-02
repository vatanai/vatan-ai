<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Generation;
use App\Models\ActivityLog; // 🟢 اضافه شد: تایم‌لاین کامل فعالیت کاربر
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * نمایش لیست تمام کاربران
     */
    public function index()
    {
        $users = User::latest()
            ->withCount('generations')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    /**
     * نمایش لاگ‌های اختصاصی یک کاربر خاص (تصاویر + تایم‌لاین کامل فعالیت)
     */
    public function logs($id)
    {
        $user = User::findOrFail($id);

        $logs = Generation::where('user_id', $id)
            ->latest()
            ->paginate(10);

        // 🟢 تایم‌لاین کامل: ثبت‌نام، ورود، خروج، آپلود، جنریت، خطاها - همه به ترتیب زمانی
        $activities = ActivityLog::where('user_id', $id)
            ->latest()
            ->paginate(30, ['*'], 'activity_page');

        return view('admin.users.logs', compact('user', 'logs', 'activities'));
    }

    /**
     * نمایش صفحه لاگ همگانی و مانیتورینگ کل سیستم (تصاویر)
     */
    public function allLogs()
    {
        $logs = Generation::with(['user', 'product'])
                      ->latest()
                      ->paginate(20);

        return view('admin.users.all-logs', compact('logs'));
    }

    /**
     * 🟢 متد جدید: مانیتورینگ زنده‌ی همگانی تمام رویدادها (نه فقط تصاویر)
     * شامل ثبت‌نام‌ها، ورودها، خروج‌ها، تلاش‌های ناموفق و خطاها در سراسر سیستم
     */
    public function allActivities()
    {
        $activities = ActivityLog::with(['user', 'prompt', 'generation'])
            ->latest()
            ->paginate(30);

        return view('admin.users.all-activities', compact('activities'));
    }
}