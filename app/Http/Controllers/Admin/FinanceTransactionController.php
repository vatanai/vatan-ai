<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceTransaction;
use App\Services\Finance\FinanceAccessService;
use App\Services\Finance\FinanceAuditService;
use App\Services\Finance\FinanceExchangeRateSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FinanceTransactionController extends Controller
{
    public function store(
        Request $request,
        FinanceAccessService $access,
        FinanceAuditService $audit,
        FinanceExchangeRateSnapshotService $rates,
    ) {
        $access->ensureWrite($request->user('admin'));
        $data = $this->validated($request, $rates);
        $data['created_by'] = $request->user('admin')->id;
        $data['source_type'] = 'manual';
        $transaction = FinanceTransaction::create($data);
        $audit->record($transaction, 'created', null, $transaction->toArray());

        return back()->with('success', 'تراکنش مالی ثبت شد.');
    }

    public function update(
        Request $request,
        FinanceTransaction $transaction,
        FinanceAccessService $access,
        FinanceAuditService $audit,
        FinanceExchangeRateSnapshotService $rates,
    ) {
        $access->ensureWrite($request->user('admin'));
        abort_unless($transaction->source_type === 'manual', 422, 'رکورد خودکار فقط از منبع اصلی بروزرسانی می‌شود.');
        $before = $transaction->toArray();
        $transaction->update($this->validated($request, $rates, $transaction));
        $audit->record($transaction, 'updated', $before, $transaction->fresh()->toArray());

        return to_route('admin.finance.show', ['section' => $transaction->direction === 'expense' ? 'expenses' : 'income'])
            ->with('success', 'تراکنش مالی بروزرسانی شد.');
    }

    public function approve(
        Request $request,
        FinanceTransaction $transaction,
        FinanceAccessService $access,
        FinanceAuditService $audit,
    ) {
        $access->ensureApprove($request->user('admin'));
        $before = $transaction->toArray();
        $transaction->update([
            'approved_by' => $request->user('admin')->id,
            'approved_at' => now(),
        ]);
        $audit->record($transaction, 'approved', $before, $transaction->fresh()->toArray());

        return back()->with('success', 'تراکنش توسط مالک تأیید شد.');
    }

    public function destroy(
        Request $request,
        FinanceTransaction $transaction,
        FinanceAccessService $access,
        FinanceAuditService $audit,
    ) {
        $access->ensureApprove($request->user('admin'));
        abort_unless($transaction->source_type === 'manual', 422, 'رکورد خودکار قابل حذف دستی نیست.');
        $before = $transaction->toArray();
        $transaction->delete();
        $audit->record($transaction, 'deleted', $before, ['deleted_at' => $transaction->deleted_at]);

        return back()->with('success', 'تراکنش حذف نرم شد و سابقه آن باقی ماند.');
    }

    private function validated(
        Request $request,
        FinanceExchangeRateSnapshotService $rates,
        ?FinanceTransaction $transaction = null,
    ): array {
        $data = $request->validate([
            'direction' => ['required', Rule::in(['income', 'expense'])],
            'category' => ['required', Rule::in(array_keys(config('finance.categories')))],
            'title' => ['required', 'string', 'max:190'],
            'amount_original' => ['required', 'numeric', 'min:0.0001'],
            'currency' => ['required', Rule::in(['IRT', 'USD'])],
            'exchange_rate_toman' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(array_keys(config('finance.statuses')))],
            'occurred_at' => ['required', 'date'],
            'due_at' => ['nullable', 'date'],
            'paid_at' => ['nullable', 'date'],
            'cost_center_id' => ['nullable', 'exists:finance_cost_centers,id'],
            'vendor_id' => ['nullable', 'exists:finance_vendors,id'],
            'payment_method_id' => ['nullable', 'exists:finance_payment_methods,id'],
            'invoice' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'tags_text' => ['nullable', 'string', 'max:500'],
        ]);
        $currency = strtoupper($data['currency']);
        $rate = $currency === 'IRT'
            ? 1
            : ((float) ($data['exchange_rate_toman'] ?? 0) ?: (float) ($transaction?->exchange_rate_toman ?? 0) ?: $rates->rateToman('USD'));

        if ($rate <= 0) {
            throw ValidationException::withMessages([
                'exchange_rate_toman' => 'برای تراکنش دلاری باید نرخ معتبر همان لحظه ثبت شود.',
            ]);
        }

        $data['currency'] = $currency;
        $data['exchange_rate_toman'] = $rate;
        $data['amount_toman'] = round((float) $data['amount_original'] * $rate, 2);
        $data['exchange_rate_irr'] = $currency === 'USD' ? $rate * 10 : 1;
        $data['amount_irr'] = $currency === 'USD' ? $data['amount_toman'] * 10 : $data['amount_toman'];
        $data['tags'] = collect(explode(',', (string) ($data['tags_text'] ?? '')))
            ->map(fn ($tag) => trim($tag))->filter()->unique()->values()->all();
        unset($data['tags_text'], $data['invoice']);

        if ($request->hasFile('invoice')) {
            $data['invoice_path'] = $request->file('invoice')->store('finance/invoices', 'public');
        }
        if ($data['status'] === 'paid' && empty($data['paid_at'])) {
            $data['paid_at'] = now();
        }

        return $data;
    }
}
