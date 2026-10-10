<?php

namespace Vatan\Seo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vatan\Seo\Models\AiCall;
use Vatan\Seo\Models\Alert;
use Vatan\Seo\Models\Run;
use Vatan\Seo\Support\Budget;
use Vatan\Seo\Support\Fa;

class ActivityController extends BaseController
{
    public function __invoke(Request $request)
    {
        $site = $this->site();
        $runs = Run::where('site_id', $site->id)->with('scenario');
        if ($agent = $request->query('agent')) {
            $runs->where('agent', $agent);
        }
        $daily = AiCall::where('site_id', $site->id)->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) d, SUM(cost_usd) c')->groupBy('d')->orderBy('d')->pluck('c', 'd');
        $days = collect(range(29, 0))->map(fn ($i) => now()->subDays($i)->toDateString());

        return $this->view('activity', [
            'runs' => $runs->latest()->paginate(40)->withQueryString(),
            'agent' => $agent,
            'byModel' => AiCall::where('site_id', $site->id)->where('created_at', '>=', now()->startOfMonth())
                ->selectRaw('model, role, COUNT(*) n, SUM(tokens_in) tin, SUM(tokens_out) tout, SUM(cost_usd) cost, SUM(CASE WHEN ok THEN 0 ELSE 1 END) errors')
                ->groupBy('model', 'role')->orderByDesc('cost')->get(),
            'byPurpose' => AiCall::where('site_id', $site->id)->where('created_at', '>=', now()->startOfMonth())
                ->selectRaw('purpose, COUNT(*) n, SUM(cost_usd) cost')->groupBy('purpose')->orderByDesc('cost')->get(),
            'costChart' => ['labels' => $days->map(fn ($d) => Fa::dayLabel($d))->values(), 'values' => $days->map(fn ($d) => round((float) ($daily[$d] ?? 0), 4))->values()],
            'budget' => ['spent' => Budget::spentThisMonth($site), 'cap' => Budget::cap($site), 'forecast' => Budget::forecast($site), 'ratio' => Budget::usageRatio($site)],
            'alerts' => Alert::where('site_id', $site->id)->latest()->limit(20)->get(),
        ]);
    }
}
