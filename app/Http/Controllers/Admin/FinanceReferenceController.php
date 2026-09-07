<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceCostCenter;
use App\Models\FinanceExchangeRate;
use App\Models\FinancePaymentMethod;
use App\Models\FinanceSetting;
use App\Models\FinanceVendor;
use App\Services\Finance\FinanceAccessService;
use App\Services\Finance\FinanceAuditService;
use App\Services\Finance\FinanceSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FinanceReferenceController extends Controller
{
    public function storeRate(Request $request, FinanceAccessService $access, FinanceAuditService $audit)
    {
        $access->ensureWrite($request->user('admin'));
        $data = $request->validate([
            'rate_date' => ['required', 'date'],
            'rate_to_toman' => ['required', 'numeric', 'min:1'],
            'source' => ['nullable', 'string', 'max:190'],
        ]);
        $existing = FinanceExchangeRate::query()->where('currency', 'USD')->whereDate('rate_date', $data['rate_date'])->first();
        $before = $existing?->toArray();
        $rate = FinanceExchangeRate::query()->updateOrCreate(
            ['currency' => 'USD', 'rate_date' => $data['rate_date']],
            [
                'rate_to_irr' => (float) $data['rate_to_toman'] * 10,
                'rate_to_toman' => $data['rate_to_toman'],
                'source' => $data['source'] ?: 'ثبت دستی',
                'is_manual' => true,
                'created_by' => $request->user('admin')->id,
            ],
        );
        $audit->record($rate, $existing ? 'rate_updated' : 'rate_created', $before, $rate->toArray());

        return back()->with('success', 'نرخ دلار روزانه ذخیره شد؛ رکوردهای قبلی همچنان نرخ خودشان را دارند.');
    }

    public function storeCostCenter(Request $request, FinanceAccessService $access, FinanceAuditService $audit)
    {
        $access->ensureWrite($request->user('admin'));
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'alpha_dash:ascii', 'max:40', 'unique:finance_cost_centers,code'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['code'] = $data['code'] ?: 'center-' . Str::lower(Str::random(8));
        $center = FinanceCostCenter::create($data + ['is_active' => true]);
        $audit->record($center, 'created', null, $center->toArray());

        return back()->with('success', 'مرکز هزینه افزوده شد.');
    }

    public function storeVendor(Request $request, FinanceAccessService $access, FinanceAuditService $audit)
    {
        $access->ensureWrite($request->user('admin'));
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'contact_name' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:190'],
            'tax_id' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $vendor = FinanceVendor::create($data + ['is_active' => true]);
        $audit->record($vendor, 'created', null, $vendor->toArray());

        return back()->with('success', 'تأمین‌کننده افزوده شد.');
    }

    public function storePaymentMethod(Request $request, FinanceAccessService $access, FinanceAuditService $audit)
    {
        $access->ensureWrite($request->user('admin'));
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'alpha_dash:ascii', 'max:40', 'unique:finance_payment_methods,code'],
        ]);
        $data['code'] = $data['code'] ?: 'method-' . Str::lower(Str::random(8));
        $method = FinancePaymentMethod::create($data + ['is_active' => true]);
        $audit->record($method, 'created', null, $method->toArray());

        return back()->with('success', 'روش پرداخت افزوده شد.');
    }

    public function updateSettings(Request $request, FinanceAccessService $access, FinanceAuditService $audit)
    {
        $access->ensureApprove($request->user('admin'));
        $rules = collect(config('finance.setting_defaults'))->mapWithKeys(
            fn ($default, $key) => [$key => ['required', 'numeric', 'min:0']]
        )->all();
        $data = $request->validate($rules);

        foreach ($data as $key => $value) {
            $setting = FinanceSetting::query()->firstOrNew(['key' => $key]);
            $before = $setting->exists ? $setting->toArray() : null;
            $setting->fill(['value' => (float) $value, 'updated_by' => $request->user('admin')->id])->save();
            $audit->record($setting, 'setting_updated', $before, $setting->toArray());
        }

        return back()->with('success', 'تنظیمات مالی برای محاسبات آینده ذخیره شد.');
    }

    public function sync(Request $request, FinanceAccessService $access, FinanceSyncService $sync)
    {
        $access->ensureWrite($request->user('admin'));
        $counts = $sync->syncExisting();

        return back()->with('success', "همگام‌سازی کامل شد: {$counts['purchases']} خرید، {$counts['orders']} سفارش و {$counts['provider_requests']} اجرای مدل.");
    }
}
