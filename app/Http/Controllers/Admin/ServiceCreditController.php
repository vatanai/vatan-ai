<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCreditAccount;
use App\Models\ServiceCreditTransaction;
use App\Services\ServiceCreditOverviewService;
use App\Services\ServiceCreditSynchronizer;
use App\Services\ServiceCreditTransactionReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ServiceCreditController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.service-credits.providers');
    }

    private function overviewData(ServiceCreditOverviewService $overview): array
    {
        // همگام‌سازی ثبت تراکنش‌ها در زمان‌بندی مستقل انجام می‌شود؛ باز شدن صفحه
        // نباید منتظر تماس‌های خارجی و قفل‌های دیتابیس بماند. موجودی زنده و هشدارها
        // همچنان توسط سرویس overview خوانده می‌شوند و دکمه تازه‌سازی دستی هم فعال است.
        $data = $overview->get();
        $data['alerts'] = $data['accounts']->filter(function ($account): bool {
            return $account->alerts_enabled && ($account->alert_level ?? null) !== null;
        })->values();
        return $data;
    }

    public function providers(
        ServiceCreditOverviewService $overview,
    ): View
    {
        return view('admin.service-credits.index', [
            ...$this->overviewData($overview),
            'mode' => 'providers',
            'providerStats' => collect(),
        ]);
    }

    public function transactions(
        ServiceCreditOverviewService $overview,
        ServiceCreditTransactionReport $transactionReport,
        Request $request
    ): View
    {
        $data = $this->overviewData($overview);
        return view('admin.service-credits.index', [
            ...$data,
            ...$transactionReport->build($request),
            'mode' => 'transactions',
        ]);
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'alpha_dash', 'max:100', 'unique:service_credit_accounts,slug'],
            'currency' => ['required', 'in:USD,IRR'],
            'manual_balance' => ['required', 'numeric', 'min:0'],
            'low_balance_threshold' => ['nullable', 'numeric', 'min:0'],
            'critical_balance_threshold' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['sync_driver'] = 'manual';
        $data['show_on_dashboard'] = $request->boolean('show_on_dashboard');
        $data['alerts_enabled'] = $request->boolean('alerts_enabled');
        ServiceCreditAccount::create($data);
        return back()->with('success', 'اکانت جدید اضافه شد.');
    }

    public function updateAccount(Request $request, ServiceCreditAccount $account): RedirectResponse
    {
        $data = $request->validate([
            'manual_balance' => ['required', 'numeric', 'min:0'],
            'low_balance_threshold' => ['nullable', 'numeric', 'min:0'],
            'critical_balance_threshold' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['show_on_dashboard'] = $request->boolean('show_on_dashboard');
        $data['alerts_enabled'] = $request->boolean('alerts_enabled');
        $account->update($data);
        foreach (['openrouter', 'liara', 'fal', 'replicate'] as $provider) {
            Cache::forget('finance.' . $provider . '_credits');
        }
        return back()->with('success', 'تنظیمات اکانت ذخیره شد.');
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'service_credit_account_id' => ['required', 'exists:service_credit_accounts,id'],
            'type' => ['required', 'in:charge,usage,refund,adjustment'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'occurred_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($data) {
            $account = ServiceCreditAccount::lockForUpdate()->findOrFail($data['service_credit_account_id']);
            $delta = in_array($data['type'], ['charge', 'refund'], true) ? $data['amount'] : -$data['amount'];
            if ($data['type'] === 'adjustment') $delta = $data['amount'];
            $account->update(['manual_balance' => max(0, (float) $account->manual_balance + $delta)]);
            ServiceCreditTransaction::create([...$data, 'admin_id' => auth('admin')->id()]);
        });

        return back()->with('success', 'تراکنش ثبت و موجودی به‌روزرسانی شد.');
    }

    public function refresh(ServiceCreditSynchronizer $synchronizer): RedirectResponse
    {
        $result = $synchronizer->sync();
        Cache::forget('finance.usd_irr');
        return back()->with(
            'success',
            "اطلاعات آنلاین تازه‌سازی شد؛ {$result['transactions_created']} تغییر جدید در تراکنش‌ها ثبت شد."
        );
    }
}
