<?php

use App\Models\LabExperiment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lab_experiments') || ! Schema::hasTable('lab_runs')) {
            return;
        }

        LabExperiment::query()
            ->whereIn('status', ['queued', 'processing'])
            ->with('runs')
            ->each(function (LabExperiment $experiment): void {
                $runs = $experiment->runs;
                if ($runs->isEmpty() || ! $runs->every(fn ($run) => in_array($run->status, ['completed', 'failed', 'cancelled'], true))) {
                    return;
                }

                $status = $runs->contains(fn ($run) => $run->status === 'completed')
                    ? ($runs->contains(fn ($run) => $run->status === 'failed') ? 'partially_failed' : 'completed')
                    : 'failed';
                $totalUsd = (float) $runs->sum(fn ($run) => (float) $run->actual_cost_usd);
                $rateIrr = (float) $experiment->exchange_rate_irr;

                $experiment->forceFill([
                    'status' => $status,
                    'actual_cost_usd' => $totalUsd,
                    'actual_cost_irr' => $totalUsd * $rateIrr,
                    'actual_cost_toman' => $totalUsd * ($rateIrr / 10),
                    'total_cost_usd' => $totalUsd,
                    'total_cost_toman' => $totalUsd * ($rateIrr / 10),
                    'models_count' => $runs->count(),
                    'started_at' => $runs->min('started_at') ?: $experiment->started_at,
                    'completed_at' => $experiment->completed_at ?: now(),
                    'tested_at' => $experiment->tested_at ?: now(),
                    'report_status' => $status === 'failed' ? 'failed' : 'ready',
                ])->save();
            });
    }

    public function down(): void
    {
        // وضعیت نهایی آزمایش‌های واقعی نباید به حالت در صف برگردد.
    }
};
