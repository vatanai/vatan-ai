<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TokenLog;
use App\Support\Jalali;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\SmsEventService;

class AdminUserController extends Controller
{
    /**
     * نمایش لیست تمام کاربران سیستم (بدون صفحه‌بندی) به همراه محصولات استفاده شده
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'birth_month' => ['nullable', 'integer', 'between:1,12', 'required_with:birth_day'],
            'birth_day' => ['nullable', 'integer', 'between:1,31'],
        ]);

        $birthMonth = isset($filters['birth_month']) ? (int) $filters['birth_month'] : null;
        $birthDay = isset($filters['birth_day']) ? (int) $filters['birth_day'] : null;
        if ($birthMonth && $birthMonth > 6 && $birthDay === 31) {
            throw ValidationException::withMessages([
                'birth_day' => 'ماه انتخاب‌شده روز ۳۱ ندارد.',
            ]);
        }
        $allUsersCount = User::count();

        // لود کردن تصاویر خلق شده به همراه محصول مربوط به هر تصویر برای نمایش در مودال
        $users = User::with(['generatedImages.product'])
            ->withCount('generatedImages')
            ->latest()
            ->get()
            ->filter(function (User $user) use ($birthMonth, $birthDay) {
                if (!$birthMonth && !$birthDay) {
                    return true;
                }
                if (!$user->birth_date) {
                    return false;
                }

                [, $jalaliMonth, $jalaliDay] = Jalali::toJalaliYmd(
                    (int) $user->birth_date->format('Y'),
                    (int) $user->birth_date->format('n'),
                    (int) $user->birth_date->format('j'),
                );

                return (!$birthMonth || $jalaliMonth === $birthMonth)
                    && (!$birthDay || $jalaliDay === $birthDay);
            })
            ->values();

        return view('admin.users.index', compact('users', 'allUsersCount', 'birthMonth', 'birthDay'));
    }

    /**
     * برگرداندن رمز ثبت‌شده‌ی کاربر فقط برای کپی؛ این عملیات رمز را تغییر نمی‌دهد.
     */
    public function copyPassword(Request $request, $id)
    {
        abort_unless($request->user('admin')?->isLeader(), 403);

        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'کاربر یافت نشد.',
            ], 404);
        }

        if (blank($user->password_reveal)) {
            return response()->json([
                'status' => 'error',
                'message' => 'برای این کاربر رمز کپی‌شدنی ثبت نشده است.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'password' => $user->password_reveal,
        ]);
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

    public function changeCustomerSegment(Request $request, $id)
    {
        $data = $request->validate([
            'customer_segment' => 'required|in:regular,loyal',
        ]);

        $user = User::findOrFail($id);
        $user->update($data);

        return response()->json([
            'status' => 'success',
            'message' => $data['customer_segment'] === 'loyal'
                ? 'کاربر به مشتری ثابت تغییر کرد.'
                : 'کاربر به گروه عادی تغییر کرد.',
        ]);
    }

    /**
     * صفحه‌ی مدیریت توکن کاربران (افزودن/کسر/تنظیم موجودی توکن)
     */
    public function tokens()
    {
        return view('admin.users.tokens');
    }

    /**
     * جستجوی کاربر بر اساس نام، نام‌خانوادگی، ایمیل یا موبایل (Ajax — برای صفحه‌ی مدیریت توکن)
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $limit = (int) $request->query('limit', 8);
        $limit = $limit > 0 && $limit <= 50 ? $limit : 8;

        if ($q === '') {
            return response()->json(['data' => []]);
        }

        $users = User::query()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhereRaw("CONCAT(COALESCE(name,''), ' ', COALESCE(last_name,'')) LIKE ?", ["%{$q}%"]);
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (User $u) => $this->formatUserForToken($u));

        return response()->json(['data' => $users]);
    }

    /**
     * جزئیات یک کاربر مشخص (برای پیش‌بارگذاری کاربر از طریق پارامتر user_id در URL)
     */
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'کاربر یافت نشد.'], 404);
        }

        return response()->json(['data' => $this->formatUserForToken($user)]);
    }

    /**
     * تاریخچه‌ی تغییرات توکن یک کاربر مشخص
     */
    public function tokenHistory($id)
    {
        $logs = TokenLog::with(['user:id,name,last_name', 'admin:id,name'])
            ->where('user_id', $id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (TokenLog $log) => $this->formatLog($log));

        return response()->json(['data' => $logs]);
    }

    /**
     * تاریخچه‌ی سراسری آخرین تغییرات توکن (همه‌ی کاربران) — برای نمایش پیش‌فرض صفحه‌ی مدیریت توکن
     */
    public function globalTokenHistory()
    {
        $logs = TokenLog::with(['user:id,name,last_name', 'admin:id,name'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (TokenLog $log) => $this->formatLog($log));

        return response()->json(['data' => $logs]);
    }

    /**
     * اعمال تغییر دستی موجودی توکن یک کاربر (افزودن / کسر / تنظیم مستقیم)
     * این عملیات هم روی دیتابیس (ستون tokens کاربر) اعمال می‌شود و هم در تاریخچه ثبت می‌شود
     * تا هم برای ادمین و هم برای خود کاربر قابل مشاهده و اعمال باشد.
     */
    public function updateToken(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:add,deduct,set',
            'amount' => 'required|integer|min:0',
            'note'   => 'nullable|string|max:255',
        ]);

        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'کاربر یافت نشد.'], 404);
        }

        $action = $request->input('action');
        $amount = (int) $request->input('amount');
        $note   = $request->input('note') ?: null;

        if ($action !== 'set' && $amount < 1) {
            return response()->json(['status' => 'error', 'message' => 'مقدار توکن باید بزرگتر از صفر باشد.'], 422);
        }

        try {
            $result = DB::transaction(function () use ($user, $action, $amount, $note, $request) {
                // قفل کردن ردیف کاربر برای جلوگیری از رقابت هم‌زمان در تغییر موجودی
                $freshUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

                $before = (int) $freshUser->tokens;

                switch ($action) {
                    case 'add':
                        $after = $before + $amount;
                        $freshUser->tokens = $after;
                        $freshUser->tokens_purchased = (int) $freshUser->tokens_purchased + $amount;
                        break;

                    case 'deduct':
                        if ($amount > $before) {
                            throw ValidationException::withMessages([
                                'amount' => 'موجودی کاربر کافی نیست.',
                            ]);
                        }
                        $after = $before - $amount;
                        $freshUser->tokens = $after;
                        break;

                    case 'set':
                    default:
                        $after = $amount;
                        $freshUser->tokens = $after;
                        break;
                }

                $freshUser->save();

                $log = TokenLog::create([
                    'user_id'        => $freshUser->id,
                    'admin_id'       => $request->user('admin')?->id,
                    'action'         => $action,
                    'amount'         => $amount,
                    'balance_before' => $before,
                    'balance_after'  => $after,
                    'note'           => $note,
                ]);

                return [$freshUser, $log];
            });
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first() ?? 'موجودی کاربر کافی نیست.';
            return response()->json(['status' => 'error', 'message' => $firstError], 422);
        }

        [$freshUser, $log] = $result;

        if ($freshUser->phone) {
            app(SmsEventService::class)->send('credit_changed', $freshUser->phone, [
                'name'=>$freshUser->name, 'phone'=>$freshUser->phone, 'amount'=>(string)$amount,
                'balance'=>(string)$freshUser->tokens, 'action'=>['add'=>'افزایش','deduct'=>'کاهش','set'=>'تنظیم'][$action],
            ]);
            app(SmsEventService::class)->notifyLowCredit($freshUser);
        }

        return response()->json([
            'status'      => 'success',
            'message'     => 'توکن با موفقیت اعمال شد.',
            'new_balance' => (int) $freshUser->tokens,
            'log'         => $this->formatLog($log->load(['user:id,name,last_name', 'admin:id,name'])),
        ]);
    }

    /**
     * فرمت یکسان کاربر برای نمایش در فرانت صفحه‌ی مدیریت توکن
     */
    private function formatUserForToken(User $user): array
    {
        return [
            'id'    => $user->id,
            'name'  => trim(($user->name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'کاربر #' . $user->id,
            'phone' => $user->phone,
            'email' => $user->email,
            'token' => (int) $user->tokens,
        ];
    }

    /**
     * فرمت یکسان یک رکورد تاریخچه‌ی توکن برای نمایش در فرانت
     */
    private function formatLog(TokenLog $log): array
    {
        return [
            'id'     => $log->id,
            'type'   => $log->action,
            'user'   => $log->user ? trim(($log->user->name ?? '') . ' ' . ($log->user->last_name ?? '')) : '—',
            'user_id'=> $log->user_id,
            'amount' => $log->amount,
            'balance_before' => $log->balance_before,
            'balance_after'  => $log->balance_after,
            'note'   => $log->note ?: null,
            'admin'  => $log->admin->name ?? null,
            'time'   => Jalali::format($log->created_at),
            'created_at' => $log->created_at?->toDateTimeString(),
        ];
    }
}
