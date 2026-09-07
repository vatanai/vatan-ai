<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Models\TokenLog;
use App\Models\ActivityLog;
use App\Models\GeneratedImage;
use App\Support\Jalali;
use App\Services\PlanCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\SmsEventService;
use App\Services\TokenGrantService;
use App\Services\Finance\FinanceCaseLedgerService;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUserController extends Controller
{
    /**
     * نمایش لیست تمام کاربران سیستم (بدون صفحه‌بندی) به همراه محصولات استفاده شده
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'birth_month' => ['nullable', 'integer', 'between:1,12', 'required_with:birth_day'],
            'birth_day' => ['nullable', 'integer', 'between:1,31'],
            'show_user' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $search = trim((string) ($filters['q'] ?? ''));
        $birthMonth = isset($filters['birth_month']) ? (int) $filters['birth_month'] : null;
        $birthDay = isset($filters['birth_day']) ? (int) $filters['birth_day'] : null;
        if ($birthMonth && $birthMonth > 6 && $birthDay === 31) {
            throw ValidationException::withMessages([
                'birth_day' => 'ماه انتخاب‌شده روز ۳۱ ندارد.',
            ]);
        }
        $allUsersCount = User::count();
        $monthlyActiveUsersCount = User::query()
            ->where('status', 'active')
            ->whereNotNull('last_login_at')
            ->whereBetween('last_login_at', [now()->startOfMonth(), now()])
            ->count();
        $totalGeneratedImagesCount = GeneratedImage::count();

        // اطلاعات عملیاتی کاربر در کاربران، سفارش‌ها و پرونده مالی از همین روابط مشترک تغذیه می‌شود.
        $userRelations = [
                'generatedImages.product',
                'generatedImages.order:id,order_number',
                'plan',
                'referrer:id,name,last_name,phone,referral_code',
                'referralConversion:id,visit_id,inviter_id,invitee_id,status',
                'referralConversion.inviter:id,name,last_name,phone,referral_code',
                'referralConversion.visit:id,referral_code,landing_url',
        ];
        if (Schema::hasTable('auth_events')) {
            $userRelations[] = 'lastSuccessfulLogin';
        }
        if (Schema::hasTable('user_token_grants')) {
            $userRelations[] = 'tokenGrants:id,user_id,remaining_amount,expires_at';
        }

        $usersQuery = User::with($userRelations)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(COALESCE(name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ["%{$search}%"]);
                });
            })
            ->withCount('generatedImages')
            ->orderByDesc('registered_at')
            ->orderByDesc('created_at');
        if (Schema::hasTable('finance_cases')) {
            $usersQuery->withCount('financeCases');
        }

        $users = $usersQuery->get()
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

        $financeCasesAvailable = Schema::hasTable('finance_cases');
        $users->each(function (User $user) use ($financeCasesAvailable): void {
            if (! $financeCasesAvailable) {
                $user->setAttribute('finance_cases_count', 0);
            }
            $user->generatedImages->each(function (GeneratedImage $image): void {
                $image->setAttribute('jalali_created_at', Jalali::formatNumeric($image->created_at));
                $image->setAttribute(
                    'admin_image_url',
                    $image->image_path ? asset('storage/' . ltrim((string) $image->image_path, '/')) : null,
                );
            });
        });

        $autoOpenUserId = isset($filters['show_user']) ? (int) $filters['show_user'] : null;

        $plans = $this->selectablePlans();

        $canManageUserPlans = (bool) $request->user('admin')?->isLeader();
        $canManageUserStatuses = (bool) $request->user('admin');
        $canBulkManageUsers = $canManageUserPlans || $canManageUserStatuses;

        return view('admin.users.index', compact(
            'users',
            'plans',
            'allUsersCount',
            'monthlyActiveUsersCount',
            'totalGeneratedImagesCount',
            'search',
            'birthMonth',
            'birthDay',
            'canManageUserPlans',
            'canManageUserStatuses',
            'canBulkManageUsers',
            'autoOpenUserId',
        ));
    }

    /** خروجی CSV کاربران، بر اساس فیلتر جاری یا ردیف‌های انتخاب‌شده. */
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'birth_month' => ['nullable', 'integer', 'between:1,12', 'required_with:birth_day'],
            'birth_day' => ['nullable', 'integer', 'between:1,31'],
            'user_ids' => ['nullable', 'array', 'max:1000'],
            'user_ids.*' => ['integer', Rule::exists('users', 'id')],
        ]);

        $birthMonth = isset($filters['birth_month']) ? (int) $filters['birth_month'] : null;
        $birthDay = isset($filters['birth_day']) ? (int) $filters['birth_day'] : null;
        if ($birthMonth && $birthMonth > 6 && $birthDay === 31) {
            throw ValidationException::withMessages([
                'birth_day' => 'ماه انتخاب‌شده روز ۳۱ ندارد.',
            ]);
        }

        $search = trim((string) ($filters['q'] ?? ''));
        $selectedUserIds = collect($filters['user_ids'] ?? [])
            ->map(fn ($userId) => (int) $userId)
            ->unique()
            ->values()
            ->all();

        $users = User::query()
            ->with([
                'plan:id,name',
                'referrer:id,name,last_name,phone,referral_code',
            ])
            ->withCount('generatedImages')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(COALESCE(name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ["%{$search}%"]);
                });
            })
            ->when($selectedUserIds !== [], fn ($query) => $query->whereIn('id', $selectedUserIds))
            ->orderByDesc('registered_at')
            ->orderByDesc('created_at')
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
            });

        return response()->streamDownload(function () use ($users): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'شناسه کاربر', 'نام', 'نام خانوادگی', 'شماره تماس', 'ایمیل', 'تاریخ تولد شمسی',
                'وضعیت کاربر', 'پلن فعال', 'گروه قیمت‌گذاری', 'اعتبار فعلی', 'کل اعتبار خریداری‌شده',
                'کل اعتبار مصرف‌شده', 'تصاویر خلق‌شده', 'دعوت از طرف', 'کد دعوت', 'تاریخ عضویت',
                'آخرین ورود', 'تعداد ورود',
            ]);

            foreach ($users as $user) {
                $referrer = $user->referrer;
                fputcsv($stream, [
                    $user->id,
                    $user->name ?: '—',
                    $user->last_name ?: '—',
                    $user->phone ?: '—',
                    $user->email ?: '—',
                    $this->jalaliDate($user->birth_date),
                    $this->userStatusLabel($user->status),
                    $user->plan_display_name,
                    ($user->customer_segment ?: 'regular') === 'loyal' ? 'مشتری ثابت' : 'کاربر عادی',
                    (int) $user->tokens,
                    (int) $user->tokens_purchased,
                    (int) $user->tokens_used,
                    (int) $user->generated_images_count,
                    $referrer ? trim(($referrer->name ?? '') . ' ' . ($referrer->last_name ?? '')) : 'ثبت‌نام مستقیم',
                    $referrer?->referral_code ?: '—',
                    Jalali::formatNumeric($user->registered_at ?: $user->created_at),
                    Jalali::formatNumeric($user->last_login_at),
                    (int) $user->login_count,
                ]);
            }

            fclose($stream);
        }, 'vatan-users-' . now()->format('Ymd-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
        abort_unless($request->user('admin'), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended', 'deleted'])],
        ]);

        $user = User::query()->findOrFail($id);
        $user->update(['status' => $data['status']]);

        return response()->json([
            'status' => 'success',
            'user_id' => $user->id,
            'user_status' => $user->status,
            'message' => 'وضعیت کاربر با موفقیت به‌روزرسانی شد.',
        ]);
    }

    /**
     * تغییر وضعیت کاربران انتخاب‌شده از فهرست کاربران.
     * «حذف شده» یک وضعیت نرم است و اطلاعات یا اعتبار کاربر را پاک نمی‌کند.
     */
    public function bulkChangeStatus(Request $request)
    {
        abort_unless($request->user('admin'), 403);

        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1', 'max:1000'],
            'user_ids.*' => ['integer', Rule::exists('users', 'id')],
            'status' => ['required', Rule::in(['active', 'suspended', 'deleted'])],
        ]);

        $ids = collect($data['user_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $updated = DB::transaction(function () use ($ids, $data) {
            $users = User::query()->whereIn('id', $ids)->lockForUpdate()->get();
            $users->each(fn (User $user) => $user->update(['status' => $data['status']]));

            return $users->count();
        });

        return response()->json([
            'status' => 'success',
            'updated' => $updated,
            'user_status' => $data['status'],
            'message' => "وضعیت {$updated} کاربر با موفقیت به‌روزرسانی شد.",
        ]);
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
     * تغییر دستی سطح پلن از فهرست کاربران.
     * این کار عمداً هیچ اعتبار یا سابقه‌ی پرداختی جدیدی ایجاد نمی‌کند؛
     * مدیریت موجودی اعتبار فقط از صفحه‌ی مستقل اعتبار انجام می‌شود.
     */
    public function changePlan(Request $request, $id)
    {
        if (! $request->user('admin')?->isLeader()) {
            return response()->json([
                'status' => 'error',
                'message' => 'فقط مدیر ارشد می‌تواند پلن کاربران را تغییر دهد.',
            ], 403);
        }

        $data = $request->validate([
            'plan_id' => ['nullable', 'integer', Rule::exists('plans', 'id')],
        ]);

        $plan = $this->findSelectablePlan($data['plan_id'] ?? null);

        $user = User::query()->with('plan')->findOrFail($id);
        $beforePlan = $user->plan;
        $user->plan()->associate($plan);
        $user->save();
        if ($beforePlan?->id !== $plan?->id) {
            try {
                app(FinanceCaseLedgerService::class)->recordPlanChange($user->fresh(), $beforePlan, $plan, $request->user('admin')?->id);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => $plan
                ? "پلن «{$plan->name}» بدون تغییر موجودی اعتبار فعال شد."
                : 'کاربر به پلن رایگان بازگشت؛ موجودی اعتبار او تغییری نکرد.',
            'plan_id' => $plan?->id,
            'plan_name' => $user->fresh('plan')->plan_display_name,
        ]);
    }

    /**
     * اعمال یک پلن برای چند کاربر منتخب از فهرست کاربران.
     * این عملیات فقط سطح پلن را تغییر می‌دهد و اعتبار یا سابقه خرید جدیدی نمی‌سازد.
     */
    public function bulkChangePlan(Request $request)
    {
        if (! $request->user('admin')?->isLeader()) {
            return response()->json([
                'status' => 'error',
                'message' => 'فقط مدیر ارشد می‌تواند پلن کاربران را تغییر دهد.',
            ], 403);
        }

        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1', 'max:1000'],
            'user_ids.*' => ['integer', Rule::exists('users', 'id')],
            'plan_id' => ['nullable', 'integer', Rule::exists('plans', 'id')],
        ]);

        $plan = $this->findSelectablePlan($data['plan_id'] ?? null);

        $ids = collect($data['user_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $updated = DB::transaction(function () use ($ids, $plan, $request) {
            // قفل ردیف‌ها مانع تداخل با خرید هم‌زمان پلن توسط همان کاربران می‌شود.
            $users = User::query()->whereIn('id', $ids)->lockForUpdate()->get();
            $users->each(function (User $user) use ($plan) {
                $beforePlan = $user->plan()->first();
                $user->plan()->associate($plan);
                $user->save();
                if ($beforePlan?->id !== $plan?->id) {
                    try {
                        app(FinanceCaseLedgerService::class)->recordPlanChange($user, $beforePlan, $plan, request()->user('admin')?->id);
                    } catch (\Throwable $exception) {
                        report($exception);
                    }
                }
            });

            return $users->count();
        });

        return response()->json([
            'status' => 'success',
            'updated' => $updated,
            'plan_id' => $plan?->id,
            'plan_name' => $plan?->name ?? 'رایگان',
            'message' => $plan
                ? "پلن «{$plan->name}» برای {$updated} کاربر فعال شد؛ موجودی اعتبار تغییری نکرد."
                : "{$updated} کاربر به پلن رایگان بازگشتند؛ موجودی اعتبارشان تغییری نکرد.",
        ]);
    }

    /**
     * صفحه‌ی مدیریت اعتبار کاربران (افزودن/کسر/تنظیم موجودی اعتبار)
     */
    public function tokens()
    {
        return view('admin.users.tokens');
    }

    private function selectablePlans()
    {
        $plans = app(PlanCatalogService::class)->homePricingPlans();

        if ($plans->isEmpty()) {
            $plans = Plan::query()
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        return $plans
            ->filter(fn (Plan $plan) => (int) $plan->offerFor(null)['price'] > 0)
            ->values();
    }

    private function findSelectablePlan(?int $planId): ?Plan
    {
        if (! $planId) {
            return null;
        }

        $plan = $this->selectablePlans()->firstWhere('id', $planId);

        if (! $plan) {
            throw ValidationException::withMessages([
                'plan_id' => 'پلن انتخاب‌شده در فهرست پلن‌های قابل عرضه نیست.',
            ]);
        }

        return $plan;
    }

    private function jalaliDate($date): string
    {
        if (!$date) {
            return '—';
        }

        [$year, $month, $day] = Jalali::toJalaliYmd(
            (int) $date->format('Y'),
            (int) $date->format('n'),
            (int) $date->format('j'),
        );

        return Jalali::toPersianDigits(sprintf('%04d/%02d/%02d', $year, $month, $day));
    }

    private function userStatusLabel(?string $status): string
    {
        return match ($status) {
            'suspended' => 'معلق',
            'deleted' => 'حذف شده',
            default => 'فعال',
        };
    }

    /** فهرست تمام رویدادهای ثبت‌شده کاربران در پنل مدیریت. */
    public function allActivities()
    {
        $activities = ActivityLog::query()
            ->with('user:id,name,last_name,phone')
            ->latest()
            ->paginate(30);

        return view('admin.users.all-activities', compact('activities'));
    }

    /** فهرست تمام خروجی‌های تصویری خلق‌شده در سامانه. */
    public function allLogs()
    {
        $generatedImages = GeneratedImage::query()
            ->with(['user:id,name,last_name,phone', 'product'])
            ->latest()
            ->paginate(24);
        $logs = $generatedImages;

        return view('admin.users.all-logs', compact('generatedImages', 'logs'));
    }

    /** تایم‌لاین رویدادها و خروجی‌های تصویری یک کاربر مشخص. */
    public function logs($id)
    {
        $user = User::query()->findOrFail($id);
        $activities = ActivityLog::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(20, ['*'], 'activities_page');
        $generatedImages = GeneratedImage::query()
            ->with('product')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(20, ['*'], 'images_page');
        $logs = $generatedImages;

        return view('admin.users.logs', compact('user', 'activities', 'generatedImages', 'logs'));
    }

    /**
     * جستجوی کاربر بر اساس نام، نام‌خانوادگی، ایمیل یا موبایل (Ajax — برای صفحه‌ی مدیریت اعتبار)
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
     * تاریخچه‌ی تغییرات اعتبار یک کاربر مشخص
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
     * تاریخچه‌ی سراسری آخرین تغییرات اعتبار (همه‌ی کاربران) — برای نمایش پیش‌فرض صفحه‌ی مدیریت اعتبار
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
     * اعمال تغییر دستی موجودی اعتبار یک کاربر (افزودن / کسر / تنظیم مستقیم)
     * این عملیات هم روی دیتابیس (ستون tokens کاربر) اعمال می‌شود و هم در تاریخچه ثبت می‌شود
     * تا هم برای ادمین و هم برای خود کاربر قابل مشاهده و اعمال باشد.
     */
    public function updateToken(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:add,deduct,set',
            'amount' => 'required|integer|min:0',
            'credit_kind' => ['nullable', Rule::in(['gift', 'plan_upgrade', 'paid_adjustment'])],
            'note'   => 'nullable|string|max:255',
            'expires_at' => ['nullable', 'date', 'after:now'],
            'send_sms' => ['nullable', 'boolean'],
        ]);

        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'کاربر یافت نشد.'], 404);
        }

        $action = $request->input('action');
        $amount = (int) $request->input('amount');
        $note   = $request->input('note') ?: null;
        $creditKind = (string) $request->input('credit_kind', 'gift');
        $expiresAt = $request->filled('expires_at') ? now()->parse($request->input('expires_at')) : null;
        $sendSms = $request->boolean('send_sms');

        if ($action !== 'set' && $amount < 1) {
            return response()->json(['status' => 'error', 'message' => 'مقدار اعتبار باید بزرگتر از صفر باشد.'], 422);
        }

        try {
            $result = DB::transaction(function () use ($user, $action, $amount, $note, $creditKind, $expiresAt, $request) {
                // قفل کردن ردیف کاربر برای جلوگیری از رقابت هم‌زمان در تغییر موجودی
                $freshUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                app(TokenGrantService::class)->expireLocked($freshUser);

                $before = (int) $freshUser->tokens;
                $promotionalBefore = $freshUser->promotionalTokenBalance();

                switch ($action) {
                    case 'add':
                        $after = $before + $amount;
                        $freshUser->tokens = $after;
                        if ($creditKind === 'paid_adjustment') {
                            $freshUser->tokens_purchased = (int) $freshUser->tokens_purchased + $amount;
                        } else {
                            $freshUser->promotional_tokens = $promotionalBefore + $amount;
                        }
                        break;

                    case 'deduct':
                        if ($amount > $before) {
                            throw ValidationException::withMessages([
                                'amount' => 'موجودی کاربر کافی نیست.',
                            ]);
                        }
                        $after = $before - $amount;
                        $freshUser->tokens = $after;
                        // از اعتبار خریداری‌شده کم می‌شود و فقط در صورت لزوم
                        // باقی‌مانده از اعتبار هدیه کم خواهد شد.
                        $paidBefore = max(0, $before - $promotionalBefore);
                        $promotionalDeduction = max(0, $amount - $paidBefore);
                        $freshUser->promotional_tokens = max(0, $promotionalBefore - $promotionalDeduction);
                        if ($promotionalDeduction > 0) {
                            app(TokenGrantService::class)->consumeLocked($freshUser, $promotionalDeduction);
                        }
                        break;

                    case 'set':
                    default:
                        $after = $amount;
                        $freshUser->tokens = $after;
                        $delta = $after - $before;
                        if ($delta > 0 && $creditKind !== 'paid_adjustment') {
                            $freshUser->promotional_tokens = $promotionalBefore + $delta;
                        } else {
                            $freshUser->promotional_tokens = min($promotionalBefore, $after);
                            if ($delta > 0) {
                                $freshUser->tokens_purchased = (int) $freshUser->tokens_purchased + $delta;
                            }
                        }
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
                    'source'         => 'manual_credit',
                    'note'           => $note,
                    'metadata'       => [
                        'credit_kind' => $creditKind,
                        'is_promotional' => $creditKind !== 'paid_adjustment',
                        'delta' => $after - $before,
                        'expires_at' => $expiresAt?->toIso8601String(),
                    ],
                ]);

                if ($action === 'add' && $creditKind !== 'paid_adjustment') {
                    app(TokenGrantService::class)->create(
                        $freshUser,
                        $amount,
                        $expiresAt,
                        $request->user('admin')?->id,
                        $log->id,
                    );
                }

                return [$freshUser, $log];
            });
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first() ?? 'موجودی کاربر کافی نیست.';
            return response()->json(['status' => 'error', 'message' => $firstError], 422);
        }

        [$freshUser, $log] = $result;

        try {
            app(FinanceCaseLedgerService::class)->recordManualCredit($log);
        } catch (\Throwable $exception) {
            report($exception);
        }

        if ($sendSms && $freshUser->phone && $action === 'add') {
            app(SmsEventService::class)->send('credit_changed', $freshUser->phone, [
                'name'=>$freshUser->name, 'phone'=>$freshUser->phone, 'amount'=>(string)$amount,
                'balance'=>(string)$freshUser->tokens, 'action'=>['add'=>'افزایش','deduct'=>'کاهش','set'=>'تنظیم'][$action],
            ]);
            app(SmsEventService::class)->notifyLowCredit($freshUser);
        }

        return response()->json([
            'status'      => 'success',
            'message'     => 'اعتبار با موفقیت اعمال شد.',
            'new_balance' => (int) $freshUser->tokens,
            'log'         => $this->formatLog($log->load(['user:id,name,last_name', 'admin:id,name'])),
        ]);
    }

    /** اعمال یکسان اعتبار روی چند کاربر منتخب از پنجره‌ی فهرست کاربران. */
    public function bulkUpdateToken(Request $request)
    {
        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1', 'max:500'],
            'user_ids.*' => ['integer', Rule::exists('users', 'id')],
            'action' => ['required', Rule::in(['add', 'deduct', 'set'])],
            'amount' => ['required', 'integer', 'min:0'],
            'credit_kind' => ['nullable', Rule::in(['gift', 'plan_upgrade', 'paid_adjustment'])],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'send_sms' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $action = $data['action'];
        $amount = (int) $data['amount'];
        if ($action !== 'set' && $amount < 1) {
            throw ValidationException::withMessages(['amount' => 'مقدار اعتبار باید بزرگتر از صفر باشد.']);
        }
        $ids = collect($data['user_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $expiresAt = !empty($data['expires_at']) ? now()->parse($data['expires_at']) : null;
        $creditKind = (string) ($data['credit_kind'] ?? 'gift');
        $adminId = $request->user('admin')?->id;
        $sendSms = (bool) ($data['send_sms'] ?? false);

        $updated = DB::transaction(function () use ($ids, $action, $amount, $expiresAt, $creditKind, $adminId, $sendSms, $data) {
            $users = User::query()->whereIn('id', $ids)->lockForUpdate()->get();
            $count = 0;
            foreach ($users as $user) {
                $before = (int) $user->tokens;
                $promotionalBefore = $user->promotionalTokenBalance();
                if ($action === 'add') {
                    $after = $before + $amount;
                    $user->tokens = $after;
                    if ($creditKind === 'paid_adjustment') $user->tokens_purchased = (int) $user->tokens_purchased + $amount;
                    else $user->promotional_tokens = $promotionalBefore + $amount;
                } elseif ($action === 'deduct') {
                    if ($amount > $before) continue;
                    $after = $before - $amount;
                    $user->tokens = $after;
                    $paidBefore = max(0, $before - $promotionalBefore);
                    $promotionalDeduction = max(0, $amount - $paidBefore);
                    $user->promotional_tokens = max(0, $promotionalBefore - $promotionalDeduction);
                    app(TokenGrantService::class)->consumeLocked($user, $promotionalDeduction);
                } else {
                    $after = $amount;
                    $user->tokens = $after;
                    $delta = $after - $before;
                    if ($delta > 0 && $creditKind !== 'paid_adjustment') $user->promotional_tokens = $promotionalBefore + $delta;
                    elseif ($delta > 0) $user->tokens_purchased = (int) $user->tokens_purchased + $delta;
                    else $user->promotional_tokens = min($promotionalBefore, $after);
                }
                $user->save();
                $log = TokenLog::create([
                    'user_id' => $user->id, 'admin_id' => $adminId, 'action' => $action,
                    'amount' => $amount, 'balance_before' => $before, 'balance_after' => $after,
                    'source' => 'manual_credit', 'note' => $data['note'] ?? null,
                    'metadata' => ['credit_kind' => $creditKind, 'is_promotional' => $creditKind !== 'paid_adjustment', 'expires_at' => $expiresAt?->toIso8601String()],
                ]);
                if ($action === 'add' && $creditKind !== 'paid_adjustment') app(TokenGrantService::class)->create($user, $amount, $expiresAt, $adminId, $log->id);
                try { app(FinanceCaseLedgerService::class)->recordManualCredit($log); } catch (\Throwable $exception) { report($exception); }
                if ($sendSms && $action === 'add' && $user->phone) {
                    app(SmsEventService::class)->send('credit_changed', $user->phone, ['name' => $user->name, 'phone' => $user->phone, 'amount' => (string) $amount, 'balance' => (string) $user->tokens, 'action' => 'افزایش']);
                }
                $count++;
            }
            return $count;
        });

        return response()->json(['status' => 'success', 'updated' => $updated, 'message' => "عملیات اعتبار برای {$updated} کاربر انجام شد."]);
    }

    /** ارسال دوباره‌ی پیامک تغییر اعتبار برای یک رکورد تاریخچه. */
    public function resendTokenSms($logId)
    {
        $log = TokenLog::with('user')->findOrFail($logId);
        if (! $log->user?->phone) return response()->json(['status' => 'error', 'message' => 'شماره موبایل کاربر ثبت نشده است.'], 422);
        $sent = app(SmsEventService::class)->send('credit_changed', $log->user->phone, [
            'name' => $log->user->name, 'phone' => $log->user->phone, 'amount' => (string) $log->amount,
            'balance' => (string) $log->balance_after, 'action' => ['add' => 'افزایش', 'deduct' => 'کاهش', 'set' => 'تنظیم'][$log->action] ?? 'تغییر',
        ], null, 'manual');
        return response()->json(['status' => $sent ? 'success' : 'error', 'message' => $sent ? 'پیامک ارسال شد.' : 'ارسال پیامک انجام نشد.'], $sent ? 200 : 422);
    }

    /**
     * فرمت یکسان کاربر برای نمایش در فرانت صفحه‌ی مدیریت اعتبار
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
     * فرمت یکسان یک رکورد تاریخچه‌ی اعتبار برای نمایش در فرانت
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
            'expires_at' => data_get($log->metadata, 'expires_at'),
        ];
    }
}
