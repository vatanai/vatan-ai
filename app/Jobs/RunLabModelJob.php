<?php

namespace App\Jobs;

use App\Models\LabExperiment;
use App\Models\LabRun;
use App\Services\AiProviderRouter;
use App\Services\OpenRouterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RunLabModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(public int $runId) {}

    public function handle(AiProviderRouter $router, OpenRouterService $openRouter): void
    {
        $run = LabRun::with(['experiment.images', 'aiModel'])->find($this->runId);
        if (!$run || in_array($run->status, ['completed', 'cancelled'], true)) return;

        $started = microtime(true);
        $run->forceFill(['status' => 'processing', 'started_at' => now(), 'error_message' => null])->save();

        try {
            $settings = (array) $run->parameters;
            $providerResolution = match ((string) ($settings['grade_resolution'] ?? $settings['resolution'] ?? '720')) {
                '480', '720', '1080' => '1K',
                '1440' => '2K',
                '2160' => '4K',
                default => (string) ($settings['resolution'] ?? '1K'),
            };
            $references = [];
            foreach ($run->experiment->images as $image) {
                if (!Storage::disk('public')->exists($image->image_path)) continue;
                $contents = Storage::disk('public')->get($image->image_path);
                $mime = $image->mime_type ?: 'image/jpeg';
                $references[] = ['type' => 'image_url', 'image_url' => ['url' => 'data:' . $mime . ';base64,' . base64_encode($contents)]];
            }

            $extra = [];
            if ($references) $extra['input_references'] = $references;
            if (($settings['seed'] ?? null) !== null && $settings['seed'] !== '') $extra['seed'] = (int) $settings['seed'];
            if ($run->experiment->negative_prompt) $extra['negative_prompt'] = $run->experiment->negative_prompt;

            $result = $router->generateImageFromPrompt(
                $run->model_id,
                $run->prompt_snapshot,
                $providerResolution,
                (string) ($settings['grade_aspect_ratio'] ?? $settings['aspect_ratio'] ?? '1:1'),
                max(1, min(4, (int) ($settings['count'] ?? 1))),
                $extra
            );

            $items = (array) ($result['data'] ?? []);
            if (!$items) throw new \RuntimeException('از مدل انتخاب‌شده خروجی تصویری دریافت نشد.');

            foreach ($items as $item) {
                $base64 = $item['b64_json'] ?? null;
                $remoteUrl = $item['url'] ?? null;
                $path = null;
                if ($base64) {
                    $path = 'lab-outputs/' . Str::uuid() . '.png';
                    Storage::disk('public')->put($path, base64_decode($base64, true));
                } elseif ($remoteUrl && filter_var($remoteUrl, FILTER_VALIDATE_URL)) {
                    try {
                        $download = Http::timeout(30)->get($remoteUrl);
                        if ($download->successful() && $download->body() !== '') {
                            $path = 'lab-outputs/' . Str::uuid() . '.png';
                            Storage::disk('public')->put($path, $download->body());
                            $remoteUrl = null;
                        }
                    } catch (\Throwable) {
                        // لینک Provider به‌عنوان پشتیبان در خروجی نگه داشته می‌شود.
                    }
                }
                if (!$path && !$remoteUrl) continue;
                $run->outputs()->create([
                    'output_path' => $path,
                    'remote_url' => $remoteUrl,
                    'status' => 'completed',
                    'metadata' => ['provider_item' => array_filter(['revised_prompt' => $item['revised_prompt'] ?? null])],
                ]);
            }

            if (!$run->outputs()->exists()) throw new \RuntimeException('فایل خروجی قابل ذخیره‌سازی نبود.');

            $scoringCost = $this->scoreOutputs($run, $openRouter);

            $usage = (array) ($result['usage'] ?? []);
            $actualCost = (float) ($usage['cost'] ?? $run->estimated_cost_usd) + $scoringCost;
            $run->forceFill([
                'status' => 'completed',
                'actual_cost_usd' => $actualCost,
                'provider_response' => array_filter([
                    'id' => $result['id'] ?? null,
                    'model' => $result['model'] ?? $run->model_id,
                    'created' => $result['created'] ?? null,
                    'usage' => $usage,
                ], fn ($value) => $value !== null && $value !== []),
                'completed_at' => now(),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ])->save();
        } catch (\Throwable $error) {
            $run->forceFill([
                'status' => 'failed',
                'error_message' => Str::limit($error->getMessage(), 1000, ''),
                'completed_at' => now(),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ])->save();
        }

        $this->refreshExperiment($run->experiment_id);
    }

    private function refreshExperiment(int $experimentId): void
    {
        $experiment = LabExperiment::with('runs')->find($experimentId);
        if (!$experiment) return;
        $runs = $experiment->runs;
        $terminal = $runs->every(fn ($run) => in_array($run->status, ['completed', 'failed', 'cancelled'], true));
        $status = $terminal
            ? ($runs->contains(fn ($run) => $run->status === 'completed') ? 'completed' : 'failed')
            : ($runs->contains(fn ($run) => $run->status === 'processing') ? 'processing' : 'queued');
        $experiment->forceFill([
            'status' => $status,
            'actual_cost_usd' => $runs->sum(fn ($run) => (float) $run->actual_cost_usd),
            'started_at' => $runs->min('started_at') ?: $experiment->started_at,
            'completed_at' => $terminal ? now() : null,
        ])->save();
    }

    private function scoreOutputs(LabRun $run, OpenRouterService $openRouter): float
    {
        $scoringModel = (string) data_get($run->parameters, 'scoring_model', 'openai/gpt-4o-mini');
        if ($scoringModel === '') return 0.0;
        $totalCost = 0.0;

        foreach ($run->outputs()->get() as $output) {
            try {
                $imageData = null;
                if ($output->output_path && Storage::disk('public')->exists($output->output_path)) {
                    $mime = Storage::disk('public')->mimeType($output->output_path) ?: 'image/png';
                    $imageData = 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($output->output_path));
                } elseif ($output->remote_url) {
                    $imageData = $output->remote_url;
                }
                if (!$imageData) continue;

                $evaluation = $openRouter->scoreLabImage($scoringModel, $run->prompt_snapshot, $imageData);
                $totalCost += (float) data_get($evaluation, 'usage.cost', 0);
                foreach ((array) ($evaluation['scores'] ?? []) as $criterion => $score) {
                    \App\Models\LabScore::updateOrCreate(
                        ['lab_run_output_id' => $output->id, 'evaluator_type' => 'ai', 'criterion' => $criterion, 'admin_id' => null],
                        ['score' => (int) $score, 'note' => $evaluation['summary'] ?: null]
                    );
                }

                $metadata = (array) $output->metadata;
                $metadata['ai_evaluation'] = [
                    'model' => $scoringModel,
                    'summary' => $evaluation['summary'] ?? null,
                    'usage' => $evaluation['usage'] ?? [],
                ];
                $output->update(['metadata' => $metadata]);
            } catch (\Throwable $error) {
                // شکست ارزیابی نباید نتیجه‌ی تولید تصویر را ناموفق کند.
                $metadata = (array) $output->metadata;
                $metadata['ai_evaluation_error'] = \Illuminate\Support\Str::limit($error->getMessage(), 500, '');
                $output->update(['metadata' => $metadata]);
            }
        }

        return $totalCost;
    }
}
