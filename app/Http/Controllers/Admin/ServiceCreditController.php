<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCreditAccount;
use App\Models\ServiceCreditTransaction;
use App\Services\ServiceCreditOverviewService;
use App\Services\ServiceCreditSynchronizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ServiceCreditController extends Controller
{
    public function index(
        ServiceCreditOverviewService $overview,
        ServiceCreditSynchronizer $synchronizer
    ): View
    {
        $synchronizer->sync();
        $data = $overview->get();
        $rate = (float) ($data['exchange']['rate'] ?? 0);
        $transactions = ServiceCreditTransaction::with('account')->latest('occurred_at')->limit(40)->get()
            ->map(function (ServiceCreditTransaction $transaction) use ($rate) {
                $amount = (float) $transaction->amount;
                $currency = $transaction->account?->currency;
                $amountUsd = $currency === 'USD' ? $amount : ($rate > 0 ? $amount / $rate : null);
                $amountToman = $currency === 'USD' ? $amount * $rate / 10 : $amount / 10;
                $transaction->setAttribute('amount_usd', $amountUsd);
                $transaction->setAttribute('amount_toman', $amountToman);
                return $transaction;
            });
        return view('admin.service-credits.index', [...$data, 'transactions' => $transactions]);
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'alpha_dash', 'max:100', 'unique:service_credit_accounts,slug'],
            'currency' => ['required', 'in:USD,IRR'],
            'manual_balance' => ['required', 'numeric', 'min:0'],
            'low_balance_threshold' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['sync_driver'] = 'manual';
        $data['show_on_dashboard'] = $request->boolean('show_on_dashboard');
        ServiceCreditAccount::create($data);
        return back()->with('success', 'اکانت جدید اضافه شد.');
    }

    public function updateAccount(Request $request, ServiceCreditAccount $account): RedirectResponse
    {
        $data = $request->validate([
            'manual_balance' => ['required', 'numeric', 'min:0'],
            'low_balance_threshold' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['show_on_dashboard'] = $request->boolean('show_on_dashboard');
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
