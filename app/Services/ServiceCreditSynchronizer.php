<?php

namespace App\Services;

use App\Models\ServiceCreditAccount;
use App\Models\ServiceCreditSnapshot;
use App\Models\ServiceCreditTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ServiceCreditSynchronizer
{
    public function __construct(private ServiceCreditOverviewService $overview) {}

    public function sync(): array
    {
        if (!Schema::hasTable('service_credit_snapshots')) {
            return ['synced' => 0, 'transactions_created' => 0, 'changes' => []];
        }

        Cache::forget('finance.openrouter_credits');
        Cache::forget('finance.liara_credits');
        $accounts = $this->overview->get()['accounts'];
        $result = ['synced' => 0, 'transactions_created' => 0, 'changes' => []];

        foreach ($accounts as $liveAccount) {
            if (!$liveAccount->is_online) continue;

            $change = DB::transaction(function () use ($liveAccount) {
                $account = ServiceCreditAccount::query()->lockForUpdate()->findOrFail($liveAccount->id);
                $previous = ServiceCreditSnapshot::query()
                    ->where('service_credit_account_id', $account->id)
                    ->latest('captured_at')
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                $currentBalance = round((float) $liveAccount->display_balance, 6);
                $account->update(['last_synced_at' => now()]);

                if (!$previous) {
                    ServiceCreditSnapshot::create([
                        'service_credit_account_id' => $account->id,
                        'balance' => $currentBalance,
                        'currency' => $account->currency,
                        'captured_at' => now(),
                    ]);
                    return ['account' => $account->name, 'delta' => 0, 'initialized' => true];
                }

                $delta = round($currentBalance - (float) $previous->balance, 6);
                $minimumDelta = $account->currency === 'USD' ? 0.000001 : 0.01;
                if (abs($delta) < $minimumDelta) {
                    return ['account' => $account->name, 'delta' => 0, 'initialized' => false];
                }

                $snapshot = ServiceCreditSnapshot::create([
                    'service_credit_account_id' => $account->id,
                    'balance' => $currentBalance,
                    'currency' => $account->currency,
                    'captured_at' => now(),
                ]);
                $type = $delta < 0 ? 'usage' : 'charge';
                $transaction = ServiceCreditTransaction::create([
                    'service_credit_account_id' => $account->id,
                    'admin_id' => null,
                    'type' => $type,
                    'amount' => abs($delta),
                    'occurred_at' => now(),
                    'reference' => 'auto-sync-' . $snapshot->id,
                    'note' => $delta < 0
                        ? 'کاهش موجودی ثبت‌شده توسط همگام‌سازی آنلاین'
                        : 'افزایش موجودی ثبت‌شده توسط همگام‌سازی آنلاین',
                ]);

                return [
                    'account' => $account->name,
                    'delta' => $delta,
                    'transaction_id' => $transaction->id,
                    'initialized' => false,
                ];
            });

            $result['synced']++;
            if (isset($change['transaction_id'])) $result['transactions_created']++;
            $result['changes'][] = $change;
        }

        return $result;
    }
}
