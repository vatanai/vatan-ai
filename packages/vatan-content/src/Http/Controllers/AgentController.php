<?php

namespace Vatan\Content\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Vatan\Content\Models\Job;
use Vatan\Content\Models\Production;
use Vatan\Content\Models\ProductionVersion;
use Vatan\Content\Models\Setting;
use Vatan\Content\Services\Library;
use Vatan\Content\Services\Telegram;

/**
 * API ایجنت مک (content-lab/_system/agent/agent.py).
 * احراز هویت: هدر X-CF-Token = توکن صفحه‌ی تنظیمات. ایجنت کار می‌گیرد، روی مک اجرا می‌کند و نتیجه را برمی‌گرداند.
 */
class AgentController extends Controller
{
    public static function token(): string
    {
        $t = (string) (config('content-factory.agent_token') ?: Setting::get('agent_token', null, (int) config('content-factory.workspace_id', 1)));
        if ($t === '') {
            $t = Str::random(40);
            Setting::put('agent_token', $t, (int) config('content-factory.workspace_id', 1));
        }

        return $t;
    }

    private function guard(Request $r): void
    {
        if (! hash_equals(self::token(), (string) $r->header('X-CF-Token'))) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json(['message' => 'توکن ایجنت نامعتبر است.'], 401));
        }
    }

    public function hello(Request $r)
    {
        $this->guard($r);
        if ($r->boolean('bye')) {
            Setting::put('agent', array_merge((array) Setting::get('agent', [], $this->ws()), ['last_seen' => null, 'pending' => $r->input('pending', [])]), $this->ws());

            return ['ok' => true];
        }
        Setting::put('agent', ['last_seen' => now()->toIso8601String(), 'host' => $r->input('host'), 'version' => $r->input('version'),
            'caps' => $r->input('caps', []), 'lab' => $r->input('lab'), 'pending' => $r->input('pending', [])], $this->ws());

        return ['ok' => true, 'server_time' => now()->toIso8601String()];
    }

    public function next(Request $r)
    {
        $this->guard($r);
        Setting::put('agent', array_merge((array) Setting::get('agent', [], $this->ws()), ['last_seen' => now()->toIso8601String()]), $this->ws());
        // کار گیرکرده (بیش از ۳۰ دقیقه بدون پایان) دوباره در صف قرار می‌گیرد
        Job::where('status', 'running')->where('claimed_at', '<', now()->subMinutes(30))->where('attempts', '<', 3)->update(['status' => 'queued']);
        $job = DB::transaction(function () use ($r) {
            $j = Job::where('workspace_id', $this->ws())->where('status', 'queued')->where('location', 'mac')->orderBy('id')->lockForUpdate()->first();
            if ($j) {
                $j->fill(['status' => 'running', 'claimed_at' => now(), 'agent' => (string) $r->input('host'), 'attempts' => $j->attempts + 1, 'message' => 'ایجنت مک شروع کرد'])->save();
            }

            return $j;
        });

        return $job ? ['job' => ['id' => $job->id, 'type' => $job->type, 'payload' => $job->payload]] : ['job' => null];
    }

    public function progress(Request $r, Job $job)
    {
        $this->guard($r);
        $job->fill(array_filter(['progress' => $r->integer('progress') ?: null, 'message' => $r->input('message')], fn ($v) => $v !== null));
        if ($r->filled('log')) {
            $job->appendLog((string) $r->input('log'));
        }
        $job->save();

        return ['ok' => true, 'cancel' => $job->status === 'cancelled'];
    }

    public function complete(Request $r, Job $job)
    {
        $this->guard($r);
        $ok = $r->input('status', 'done') === 'done';
        $job->fill(['status' => $ok ? 'done' : 'failed', 'progress' => $ok ? 100 : $job->progress, 'finished_at' => now(),
            'result' => $r->input('result'), 'message' => $r->input('message', $ok ? 'انجام شد' : 'خطا')]);
        if ($r->filled('log')) {
            $job->appendLog((string) $r->input('log'));
        }
        if ($ok && $job->type === 'ai_write') {
            $item = \Vatan\Content\Models\CalendarItem::find($job->payload['item_id'] ?? 0);
            $applied = $item ? app(\Vatan\Content\Services\MasterPrompt::class)->apply($item, (string) ($r->input('result.text') ?? '')) : null;
            $job->message = $applied ? 'Claude Code نوشت: '.$applied : 'خروجی Claude Code قابل استفاده نبود';
            $job->status = $applied ? 'done' : 'failed';
            $job->result = ['applied' => (bool) $applied, 'chars' => mb_strlen((string) $r->input('result.text'))];
            $job->save();

            return ['ok' => true];
        }
        $job->save();
        if (! $ok && $job->production_id && $job->type === 'render') {
            Production::whereKey($job->production_id)->update(['status' => 'queued']);
        }

        return ['ok' => true];
    }

    /** آپلود فایل خروجی/پیش‌نمایش → دیسک عمومی */
    public function upload(Request $r)
    {
        $this->guard($r);
        $r->validate(['file' => 'required|file|max:204800', 'dir' => 'required|string|max:120']);
        $dir = trim(config('content-factory.disk_root', 'content-factory'), '/').'/'.trim(preg_replace('~[^a-zA-Z0-9_\-/]~', '', $r->input('dir')), '/');
        $name = preg_replace('~[^a-zA-Z0-9_\-.]~', '', $r->file('file')->getClientOriginalName()) ?: Str::random(8);
        $path = $r->file('file')->storeAs($dir, $name, config('content-factory.disk', 'public'));

        return ['path' => $path, 'url' => Storage::disk(config('content-factory.disk', 'public'))->url($path)];
    }

    /** آپلود تکه‌ای (۱ مگابایتی) تا محدودیت upload_max_filesize سرور مانع فایل‌های بزرگ نشود */
    public function chunk(Request $r)
    {
        $this->guard($r);
        $r->validate(['chunk' => 'required|file|max:4096', 'upload_id' => 'required|alpha_num|max:40', 'index' => 'required|integer|min:0',
            'total' => 'required|integer|min:1|max:2000', 'name' => 'required|string|max:120', 'dir' => 'required|string|max:120']);
        $tmp = storage_path('app/cf-chunks/'.$r->input('upload_id'));
        @mkdir($tmp, 0775, true);
        $r->file('chunk')->move($tmp, sprintf('%05d', $r->integer('index')));
        if (count(glob($tmp.'/*')) < $r->integer('total')) {
            return ['ok' => true, 'done' => false];
        }
        $name = preg_replace('~[^a-zA-Z0-9_\-.]~', '', $r->input('name')) ?: Str::random(8);
        $dir = trim(config('content-factory.disk_root', 'content-factory'), '/').'/'.trim(preg_replace('~[^a-zA-Z0-9_\-/]~', '', $r->input('dir')), '/');
        $assembled = $tmp.'/__file';
        $out = fopen($assembled, 'wb');
        foreach (range(0, $r->integer('total') - 1) as $i) {
            $part = $tmp.'/'.sprintf('%05d', $i);
            $in = fopen($part, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);
        $disk = Storage::disk(config('content-factory.disk', 'public'));
        $disk->putFileAs($dir, new \Illuminate\Http\File($assembled), $name);
        foreach (glob($tmp.'/*') as $f) {
            @unlink($f);
        }
        @rmdir($tmp);

        return ['ok' => true, 'done' => true, 'path' => $dir.'/'.$name, 'url' => $disk->url($dir.'/'.$name)];
    }

    public function reference(Request $r, Library $lib)
    {
        $this->guard($r);
        [$ref, $c] = $lib->saveReference((array) $r->input('meta'), $r->input('sheet_path'));

        return ['reference_id' => $ref->id, 'style' => $c['style']?->slug, 'style_name' => $c['style']?->name_fa, 'folder' => $c['folder'],
            'confidence' => $c['confidence'], 'reason' => $c['reason']];
    }

    public function moved(Request $r)
    {
        $this->guard($r);
        $kind = $r->input('kind');
        $model = $kind === 'music' ? \Vatan\Content\Models\MusicTrack::class : \Vatan\Content\Models\Reference::class;
        $col = $kind === 'music' ? 'library_path' : 'moved_path';
        $model::where('workspace_id', $this->ws())->where('slug', $r->input('slug'))->update([$col => $r->input('path')]);

        return ['ok' => true];
    }

    /** جای درست فایل‌ها پس از تغییر دستی سبک در داشبورد */
    public function assignments(Request $r)
    {
        $this->guard($r);
        $styles = \Vatan\Content\Models\Style::where('workspace_id', $this->ws())->get(['id', 'slug', 'folder']);
        $refs = \Vatan\Content\Models\Reference::where('workspace_id', $this->ws())->whereNotNull('moved_path')->get(['slug', 'style_id', 'moved_path']);

        return ['styles' => $styles->map(fn ($s) => $s->folder ?: $s->slug)->values(),
            'references' => $refs->map(fn ($x) => ['slug' => $x->slug, 'path' => $x->moved_path,
                'folder' => ($st = $styles->firstWhere('id', $x->style_id)) ? ($st->folder ?: $st->slug) : '_unsorted'])->values()];
    }

    public function music(Request $r, Library $lib)
    {
        $this->guard($r);
        $t = $lib->saveMusic((array) $r->input('meta'), $r->input('audio_path'));

        return ['music_id' => $t->id, 'folder' => $t->folder, 'label' => $t->mood];
    }

    /** نسخه‌ی جدید تولید پس از رندر روی مک */
    public function version(Request $r, Production $production, Telegram $tg)
    {
        $this->guard($r);
        $files = (array) $r->input('files', []);
        $v = (int) ($r->input('version') ?: $production->current_version + 1);
        $pv = ProductionVersion::updateOrCreate(['production_id' => $production->id, 'version' => $v], [
            'video_path' => $files['video'] ?? null, 'cover_path' => $files['cover'] ?? ($files['slides'][0] ?? null),
            'stories' => $files['stories'] ?? [], 'slides' => $files['slides'] ?? [], 'duration' => $r->input('duration'),
            'notes' => $r->input('notes'), 'qc' => $r->input('qc'), 'status' => 'review',
            'build_seconds' => $r->integer('build_seconds') ?: null,
            'build_prompt' => data_get($production->jobs()->where('type', 'render')->first(), 'payload.prompt') ?? $production->build_prompt,
        ]);
        $production->update(['current_version' => $v, 'cover_path' => $pv->cover_path, 'status' => 'review',
            'build_seconds' => $r->integer('build_seconds') ?: $production->build_seconds]);
        $production->item?->update(['production_status' => 'review']);
        $production->edits()->where('status', 'open')->update(['status' => 'done', 'resolved_version' => $v]);
        \Illuminate\Support\Facades\Cache::forget('cf:review-badge');
        $tg->notifyReview($production->fresh(['versions', 'item']));

        return ['ok' => true, 'version' => $v];
    }
}
