<?php

namespace Vatan\Seo\Http\Controllers;

use Illuminate\Http\Request;
use Vatan\Seo\Models\Run;
use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Services\ScenarioRunner;
use Vatan\Seo\Services\Scheduler;

class ScenarioController extends BaseController
{
    public function index(Scheduler $scheduler)
    {
        $site = $this->site();
        $scenarios = Scenario::where('site_id', $site->id)->orderByRaw("CASE frequency WHEN 'daily' THEN 0 WHEN 'twice_weekly' THEN 1 WHEN 'weekly' THEN 2 ELSE 3 END")->orderBy('at')->get();
        return $this->view('scenarios', [
            'scenarios' => $scenarios,
            'effective' => $scenarios->mapWithKeys(fn ($s) => [$s->id => $scheduler->effectiveFrequency($s)]),
            'runs' => Run::where('site_id', $site->id)->whereNotNull('scenario_id')->latest()->limit(60)->get()->groupBy('scenario_id'),
        ]);
    }

    public function update(Request $request, Scenario $scenario, Scheduler $scheduler)
    {
        abort_unless($scenario->site_id === $this->site()->id, 404);
        $data = $request->validate([
            'is_enabled' => 'nullable|boolean',
            'frequency' => 'nullable|in:daily,twice_weekly,weekly,monthly',
            'at' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'weekday' => 'nullable|integer|min:0|max:6',
            'day' => 'nullable|integer|min:1|max:28',
            'follow_profile' => 'nullable|boolean',
        ]);
        if ($request->has('toggle')) {
            $scenario->is_enabled = ! $scenario->is_enabled;
        } else {
            $scenario->fill(array_filter($data, fn ($v) => $v !== null));
            $scenario->follow_profile = $request->boolean('follow_profile');
        }
        $scenario->next_run_at = $scheduler->nextRun($scenario);
        $scenario->save();
        return back()->with('success', '«'.$scenario->title.'» ذخیره شد.');
    }

    public function run(Scenario $scenario, ScenarioRunner $runner)
    {
        abort_unless($scenario->site_id === $this->site()->id, 404);
        @set_time_limit(1200);
        $run = $runner->run($scenario, 'manual');
        if (! $run) {
            return back()->with('warning', 'این سناریو همین حالا در حال اجراست.');
        }
        return back()->with(['success' => 'success', 'warning' => 'warning', 'skipped' => 'warning', 'failed' => 'error'][$run->status] ?? 'success', $run->summary);
    }
}
