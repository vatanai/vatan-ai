<?php

namespace Vatan\Content\Http\Controllers;

use Illuminate\Http\Request;
use Vatan\Content\Models\Job;
use Vatan\Content\Models\Setting;
use Vatan\Content\Support\Fa;

class JobController extends Controller
{
    public function store(Request $request)
    {
        $d = $request->validate(['type' => 'required|in:ingest_videos,ingest_music,sync_files']);
        $exists = Job::where('workspace_id', $this->ws())->where('type', $d['type'])->whereIn('status', ['queued', 'running'])->exists();
        if ($exists) {
            return back()->with('warning', 'همین کار الان در صف یا در حال اجراست.');
        }
        Job::create(['workspace_id' => $this->ws(), 'type' => $d['type'], 'location' => 'mac', 'status' => 'queued', 'created_by' => $this->adminName(), 'payload' => []]);
        $spawned = self::spawnLocal();

        return back()->with('success', Job::TYPES[$d['type']].' در صف ایجنت مک قرار گرفت.'.(self::agentOnline() || $spawned ? '' : ' ⚠️ ایجنت مک الان آنلاین نیست؛ به‌محض روشن شدن اجرا می‌شود.'));
    }

    public function cancel(Job $job)
    {
        abort_unless($job->workspace_id === $this->ws(), 404);
        if (in_array($job->status, ['queued', 'running'], true)) {
            $job->update(['status' => 'cancelled', 'finished_at' => now(), 'message' => 'لغو توسط '.$this->adminName()]);
        }

        return back()->with('success', 'کار لغو شد.');
    }

    /**
     * اگر داشبورد روی همان مکی اجرا می‌شود که content-lab دارد (MAMP)، ایجنت را همان لحظه در پس‌زمینه برای کارهای صف روشن می‌کند؛
     * دیگر لازم نیست ایجنت جدا باز باشد. روی سرور اصلی کاری نمی‌کند.
     */
    public static function spawnLocal(): bool
    {
        $agent = rtrim((string) config('content-factory.lab_path'), '/').'/_system/agent/agent.py';
        if (! config('content-factory.runner.inline', true) || ! is_file($agent) || ! function_exists('exec') || self::agentOnline()) {
            return false;
        }
        $python = (string) config('content-factory.runner.python', 'python3');
        $path = implode(':', (array) config('content-factory.runner.extra_paths', [])).':'.getenv('PATH');
        $log = rtrim((string) config('content-factory.lab_path'), '/').'/_system/agent/agent.log';
        $cmd = 'PATH='.escapeshellarg($path).' nohup '.escapeshellcmd($python).' '.escapeshellarg($agent)
            .' --once --server '.escapeshellarg(rtrim((string) config('content-factory.runner.local_url', config('app.url')), '/'))
            .' --token '.escapeshellarg(AgentController::token()).' >> '.escapeshellarg($log).' 2>&1 &';
        @exec($cmd);

        return true;
    }

    public static function agentOnline(): bool
    {
        $a = (array) Setting::get('agent', [], (int) config('content-factory.workspace_id', 1));

        return ! empty($a['last_seen']) && now()->diffInSeconds(\Carbon\Carbon::parse($a['last_seen'])) < 45;
    }

    public function feed()
    {
        $jobs = Job::where('workspace_id', $this->ws())->latest('id')->limit(12)->get();
        $agent = (array) Setting::get('agent', [], $this->ws());

        return [
            'agent' => ['online' => self::agentOnline(), 'host' => $agent['host'] ?? null, 'last_seen' => isset($agent['last_seen']) ? Fa::digits(\Carbon\Carbon::parse($agent['last_seen'])->locale('fa')->diffForHumans()) : null,
                'pending' => $agent['pending'] ?? []],
            'jobs' => $jobs->map(fn ($j) => ['id' => $j->id, 'type' => Job::TYPES[$j->type] ?? $j->type, 'status' => $j->status, 'status_fa' => Job::STATUSES[$j->status] ?? $j->status,
                'progress' => $j->progress, 'message' => $j->message, 'at' => Fa::digits($j->updated_at?->locale('fa')->diffForHumans()), 'production' => $j->production_id ? route('cf.productions.show', $j->production_id) : null]),
        ];
    }
}
