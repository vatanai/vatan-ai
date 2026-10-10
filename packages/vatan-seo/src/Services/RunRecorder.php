<?php

namespace Vatan\Seo\Services;

use Vatan\Seo\Ai\RunContext;
use Vatan\Seo\Models\AiCall;
use Vatan\Seo\Models\Run;
use Vatan\Seo\Models\Site;

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
            report($e);
            [$status, $summary, $output] = ['failed', mb_substr($e->getMessage(), 0, 1000), ['exception' => class_basename($e)]];
        } finally {
            RunContext::$runId = $previous;
        }
        $run->update([
            'status' => $status,
            'summary' => $summary,
            'output' => $output,
            'cost_usd' => (float) AiCall::where('run_id', $run->id)->sum('cost_usd'),
            'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            'finished_at' => now(),
        ]);

        return $run;
    }
}
