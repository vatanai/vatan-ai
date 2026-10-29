<?php

namespace Vatan\Seo\Services;

use Illuminate\Support\Facades\Log;
use Vatan\Seo\Ai\RunContext;
use Vatan\Seo\Models\AiCall;
use Vatan\Seo\Models\Run;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Support\SecretRedactor;

/** هر کاری که ایجنت انجام می‌دهد، یک «اجرا» با خلاصه، خروجی، مدت و هزینه‌ی دقیق ثبت می‌کند. */
class RunRecorder
{
    /**
     * @param callable(Run):array{0:string,1:string,2?:array} $work خروجی: [status, summary, output]
     */
    public function record(Site $site, string $agent, string $action, callable $work, array $ctx = []): Run
    {
        $run = Run::create([
            'site_id' => $site->id,
            'scenario_id' => $ctx['scenario_id'] ?? null,
            'task_id' => $ctx['task_id'] ?? null,
            'agent' => $agent,
            'action' => $action,
            'status' => 'running',
            'trigger' => $ctx['trigger'] ?? 'schedule',
            'started_at' => now(),
        ]);
        $previous = RunContext::$runId;
        RunContext::$runId = $run->id;
        $started = microtime(true);
        try {
            $result = $work($run);
            [$status, $summary] = [$result[0] ?? 'success', $result[1] ?? null];
            $output = $result[2] ?? null;
        } catch (\Throwable $e) {
            Log::error('SEO scenario failed', [
                'exception' => class_basename($e),
                'message' => SecretRedactor::redact($e->getMessage()),
            ]);
            [$status, $summary, $output] = ['failed', SecretRedactor::redact($e->getMessage()), ['exception' => class_basename($e)]];
        } finally {
            RunContext::$runId = $previous;
        }
        $run->update([
            'status' => $status,
            'summary' => is_string($summary) ? SecretRedactor::redact($summary) : $summary,
            'output' => SecretRedactor::sanitize($output),
            'cost_usd' => (float) AiCall::where('run_id', $run->id)->sum('cost_usd'),
            'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            'finished_at' => now(),
        ]);

        return $run;
    }
}
