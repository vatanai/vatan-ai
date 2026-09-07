<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceCase;
use App\Models\User;
use App\Services\Finance\FinanceAccessService;
use App\Services\Finance\FinanceCaseAnalysisService;
use App\Services\Finance\FinanceCaseLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FinanceCaseController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_test' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:open,closed'],
        ]);
        $hasAuthEvents = Schema::hasTable('auth_events');
        $query = FinanceCase::query()->with([
            'user' => function ($userQuery) use ($hasAuthEvents): void {
                $userQuery->select([
                    'id', 'name', 'last_name', 'phone', 'email', 'tokens', 'plan_id',
                    'registered_at', 'last_login_at', 'login_count', 'created_at',
                ])->with('plan:id,name')->withCount(['generatedImages', 'financeCases']);
                if ($hasAuthEvents) {
                    $userQuery->with('lastSuccessfulLogin');
                }
            },
            'purchase:id,order_number,plan_name,paid_amount,granted_tokens,status,purchased_at',
        ])
            ->withSum('lots as credits_granted', 'credits_granted')
            ->withSum('lots as credits_remaining', 'credits_remaining')
            ->withSum('allocations as recognized_revenue', 'revenue_toman')
            ->withCount('allocations');
        $query->when($filters['user_id'] ?? null, fn ($builder, $userId) => $builder->where('user_id', $userId));
        $query->when(isset($filters['is_test']), fn ($builder) => $builder->where('is_test', (bool) $filters['is_test']));
        $query->when($filters['status'] ?? null, fn ($builder, $status) => $builder->where('status', $status));
        $query->when(trim((string) ($filters['q'] ?? '')) !== '', function ($builder) use ($filters): void {
            $term = trim((string) $filters['q']);
            $builder->where(function ($nested) use ($term): void {
                $nested->where('case_number', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%"))
                    ->orWhereHas('purchase', fn ($purchaseQuery) => $purchaseQuery
                        ->where('order_number', 'like', "%{$term}%")
                        ->orWhere('plan_name', 'like', "%{$term}%"));
            });
        });

        $cases = $query->latest('started_at')->paginate(20)->withQueryString();
        $stats = [
            'total' => FinanceCase::query()->count(),
            'open' => FinanceCase::query()->where('status', 'open')->count(),
            'test' => FinanceCase::query()->where('is_test', true)->count(),
            'users' => FinanceCase::query()->distinct('user_id')->count('user_id'),
        ];
        $selectedUser = isset($filters['user_id']) ? User::query()->find($filters['user_id']) : null;

        return view('admin.finance.cases.index', compact('cases', 'stats', 'selectedUser'));
    }

    public function show(FinanceCase $financeCase, FinanceCaseAnalysisService $analysis)
    {
        return view('admin.finance.cases.show', $analysis->analyze($financeCase));
    }

    public function update(Request $request, FinanceCase $financeCase, FinanceAccessService $access)
    {
        $access->ensureWrite($request->user('admin'));
        $data = $request->validate([
            'is_test' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:open,closed'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
        if (array_key_exists('status', $data)) {
            $data['closed_at'] = $data['status'] === 'closed' ? now() : null;
            $data['ended_at'] = $data['status'] === 'closed' ? ($financeCase->ended_at ?: now()) : null;
        }
        $financeCase->update($data);

        return back()->with('success', 'مشخصات پرونده مالی ذخیره شد.');
    }

    public function sync(Request $request, FinanceAccessService $access, FinanceCaseLedgerService $ledger)
    {
        $access->ensureWrite($request->user('admin'));
        $counts = $ledger->syncExisting();

        return back()->with('success', "پرونده‌ها همگام شدند: {$counts['purchases']} خرید، {$counts['token_logs']} تغییر اعتبار، {$counts['orders']} سفارش و {$counts['provider_requests']} اجرای مدل.");
    }
}
