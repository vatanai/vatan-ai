<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminManagerController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureLeader($request);

        return view('admin.settings.admins', [
            'admins' => Admin::query()->orderByRaw("role = 'leader' desc")->latest('id')->get(),
            'editingAdmin' => $request->integer('edit')
                ? Admin::query()->findOrFail($request->integer('edit'))
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureLeader($request);
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['password_reveal'] = $data['password'];

        Admin::create($data);

        return to_route('admin.settings.admins')->with('success', 'مدیر جدید با موفقیت ساخته شد.');
    }

    public function update(Request $request, Admin $admin): RedirectResponse
    {
        $this->ensureLeader($request);
        $data = $this->validated($request, $admin);
        $data['is_active'] = $request->boolean('is_active');

        if ($admin->isLeader() && ($data['role'] !== 'leader' || ! $data['is_active'])) {
            $this->ensureAnotherLeaderExists($admin);
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
            unset($data['password_reveal']);
        } else {
            $data['password_reveal'] = $data['password'];
        }

        $admin->update($data);

        return to_route('admin.settings.admins')->with('success', 'اطلاعات مدیر به‌روزرسانی شد.');
    }

    public function destroy(Request $request, Admin $admin): RedirectResponse
    {
        $this->ensureLeader($request);

        abort_if($request->user('admin')->is($admin), 422, 'نمی‌توانید حسابی را که با آن وارد شده‌اید حذف کنید.');
        if ($admin->isLeader()) {
            $this->ensureAnotherLeaderExists($admin);
        }

        $admin->delete();

        return to_route('admin.settings.admins')->with('success', 'حساب مدیر حذف شد.');
    }

    public function copyPassword(Request $request, Admin $admin)
    {
        $this->ensureLeader($request);

        if (blank($admin->password_reveal)) {
            return response()->json([
                'status' => 'error',
                'message' => 'برای این مدیر رمز کپی‌شدنی ثبت نشده است.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'password' => $admin->password_reveal,
        ]);
    }

    private function validated(Request $request, ?Admin $admin = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('admins')->ignore($admin)],
            'phone' => ['required', 'regex:/^09\d{9}$/', Rule::unique('admins')->ignore($admin)],
            'role' => ['required', Rule::in(['leader', 'admin'])],
            'password' => [$admin ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'نام مدیر را وارد کنید.',
            'email.required' => 'جیمیل مدیر را وارد کنید.',
            'email.email' => 'فرمت جیمیل معتبر نیست.',
            'email.unique' => 'این جیمیل قبلاً ثبت شده است.',
            'phone.required' => 'شماره موبایل مدیر را وارد کنید.',
            'phone.regex' => 'شماره موبایل باید با 09 شروع شود و 11 رقم باشد.',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'password.required' => 'رمز عبور را وارد کنید.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز عبور یکسان نیست.',
        ]);
    }

    private function ensureLeader(Request $request): void
    {
        abort_unless($request->user('admin')?->isLeader(), 403);
    }

    private function ensureAnotherLeaderExists(Admin $admin): void
    {
        abort_unless(
            Admin::query()->where('role', 'leader')->where('is_active', true)->whereKeyNot($admin->getKey())->exists(),
            422,
            'حداقل یک رهبر فعال باید در سیستم باقی بماند.'
        );
    }
}
