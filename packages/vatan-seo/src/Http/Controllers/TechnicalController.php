<?php

namespace Vatan\Seo\Http\Controllers;

use Vatan\Seo\Agents\Advisor;
use Vatan\Seo\Models\Audit;
use Vatan\Seo\Models\Task;
use Vatan\Seo\Services\RunRecorder;

class TechnicalController extends BaseController
{
    public function index()
    {
        $site = $this->site();
        $crawl = Audit::where('site_id', $site->id)->where('type', 'crawl')->latest()->first();
        return $this->view('technical', [
            'crawl' => $crawl,
            'crawlHistory' => Audit::where('site_id', $site->id)->where('type', 'crawl')->latest()->limit(8)->get(['id', 'score', 'created_at'])->reverse()->values(),
            'speed' => Audit::where('site_id', $site->id)->where('type', 'pagespeed')->latest()->first(),
            'health' => Audit::where('site_id', $site->id)->where('type', 'health')->latest()->first(),
            'tasks' => Task::where('site_id', $site->id)->where('pillar', 'infrastructure')->orderByRaw("CASE status WHEN 'needs_action' THEN 0 WHEN 'todo' THEN 1 ELSE 2 END")->orderByDesc('priority')->get()->groupBy('category'),
            'cannibal' => (array) $site->setting('last_cannibalization.items', []),
            'llms' => $site->setting('llms_txt'),
            'geo' => (array) $site->setting('geo_probe.last', []),
            'geoHistory' => (array) $site->setting('geo_probe.history', []),
            'competitors' => (array) $site->setting('competitors_detail.items', []),
            'competitorsAt' => $site->setting('competitors_detail.at'),
        ]);
    }

    public function geoProbe(Advisor $advisor, RunRecorder $recorder)
    {
        $site = $this->site();
        @set_time_limit(300);
        $run = $recorder->record($site, 'researcher', 'geo_probe', function () use ($advisor, $site) {
            $g = $advisor->geoProbe($site);
            return ['success', 'برند در '.\Vatan\Seo\Support\Fa::n($g['mentioned']).' از '.\Vatan\Seo\Support\Fa::n($g['asked']).' پاسخ هوش مصنوعی پیشنهاد شد.'];
        }, ['trigger' => 'manual']);
        return back()->with($run->status === 'failed' ? 'error' : 'success', $run->summary);
    }

    public function competitors(Advisor $advisor, RunRecorder $recorder)
    {
        $site = $this->site();
        @set_time_limit(300);
        $run = $recorder->record($site, 'researcher', 'competitors', function () use ($advisor, $site) {
            $list = $advisor->competitors($site);
            return ['success', \Vatan\Seo\Support\Fa::n(count($list)).' رقیب از نتایج زنده‌ی گوگل پیدا شد.'];
        }, ['trigger' => 'manual']);
        return back()->with($run->status === 'failed' ? 'error' : 'success', $run->summary);
    }

    public function llms(Advisor $advisor, RunRecorder $recorder)
    {
        $site = $this->site();
        $run = $recorder->record($site, 'strategist', 'llms_txt', function () use ($advisor, $site) {
            $advisor->llmsTxt($site);
            return ['success', 'فایل llms.txt ساخته شد و در آدرس /llms.txt سرو می‌شود.'];
        }, ['trigger' => 'manual']);
        return back()->with($run->status === 'failed' ? 'error' : 'success', $run->summary);
    }
}
