<?php

namespace Database\Seeders;

use App\Models\AiModel;
use App\Models\LabAuditLog;
use App\Models\LabExperiment;
use App\Models\LabRun;
use App\Models\LabScore;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class LabDemoSeeder extends Seeder
{
    public function run(): void
    {
        $models = AiModel::query()->where('is_active', true)->where('output_modality', 'image')->orderBy('id')->take(3)->get();
        if ($models->count() < 2) return;

        $products = Product::query()->whereIn('id', [38, 39, 40])->orderBy('id')->get();
        if ($products->isEmpty()) $products = Product::query()->whereNotNull('cover')->orWhereNotNull('thumbnail')->orderBy('id')->take(3)->get();

        $grades = [
            'economic' => ['label' => 'اقتصادی', 'resolution' => '720', 'aspect_ratio' => '4:5'],
            'standard' => ['label' => 'استاندارد', 'resolution' => '1080', 'aspect_ratio' => '4:5'],
            'professional' => ['label' => 'حرفه‌ای', 'resolution' => '2160', 'aspect_ratio' => '4:5'],
        ];

        foreach ($products->take(3) as $product) {
            $imagePath = collect([$product->cover, $product->thumbnail, ...(array) $product->sample_outputs])->filter()->first();
            if (!$imagePath || !Storage::disk('public')->exists($imagePath)) continue;

            $experiment = LabExperiment::updateOrCreate(
                ['product_id' => $product->id, 'title' => '[دمو] رتبه‌بندی سه گرید — ' . $product->name_fa],
                [
                    'status' => 'completed',
                    'prompt_snapshot' => (string) ($product->prompt_template ?: 'تصویر باکیفیت محصول برای آزمایش مدل‌ها'),
                    'negative_prompt' => $product->negative_prompt,
                    'settings' => ['count' => 1, 'resolution' => '1080', 'aspect_ratio' => '4:5', 'scoring_model' => 'openai/gpt-4o-mini', 'grades' => collect($grades)->map(fn ($grade, $key) => $grade + ['key' => $key, 'model_ids' => $models->pluck('id')->values()->all()])->all()],
                    'estimated_cost_usd' => $models->sum(fn ($model) => (float) $model->cost_per_generation_usd) * 3,
                    'actual_cost_usd' => $models->sum(fn ($model) => (float) $model->cost_per_generation_usd) * 3,
                    'exchange_rate_irr' => 0,
                    'completed_at' => now(),
                ]
            );

            $experiment->images()->delete();
            $experiment->images()->create(['image_path' => $imagePath, 'role' => 'reference', 'source' => 'product']);
            $experiment->runs()->delete();

            $gradeConfig = [];
            foreach ($grades as $gradeIndex => $grade) {
                $primaryModel = $models->get($gradeIndex === 'economic' ? 0 : ($gradeIndex === 'standard' ? min(1, $models->count() - 1) : min(2, $models->count() - 1)));
                $fallbackModel = $models->first(fn ($model) => $model->id !== $primaryModel->id);
                $runs = collect([$primaryModel, $fallbackModel])->filter()->values();

                foreach ($runs as $attemptIndex => $model) {
                    $base = $gradeIndex === 'economic' ? 3.5 : ($gradeIndex === 'standard' ? 4.1 : 4.5);
                    $score = min(5, max(1, $base - ($attemptIndex * 0.35) + (($product->id % 3) * 0.08)));
                    $run = $experiment->runs()->create([
                        'ai_model_id' => $model->id,
                        'model_id' => $model->openrouter_model_id,
                        'provider' => $model->provider,
                        'alias' => $model->name,
                        'grade_key' => $gradeIndex,
                        'grade_label' => $grade['label'],
                        'role' => $attemptIndex === 0 ? 'primary' : 'fallback',
                        'attempt_order' => $attemptIndex + 1,
                        'status' => 'completed',
                        'prompt_snapshot' => (string) ($product->prompt_template ?: 'تصویر محصول'),
                        'model_snapshot' => $model->toArray(),
                        'parameters' => ['count' => 1, 'resolution' => $grade['resolution'], 'aspect_ratio' => $grade['aspect_ratio'], 'scoring_model' => 'openai/gpt-4o-mini', 'grade_key' => $gradeIndex],
                        'estimated_cost_usd' => (float) $model->cost_per_generation_usd,
                        'actual_cost_usd' => (float) $model->cost_per_generation_usd,
                        'duration_ms' => 2100 + ($attemptIndex * 480) + ($product->id % 4) * 100,
                        'started_at' => now()->subSeconds(4),
                        'completed_at' => now()->subSeconds(1),
                        'final_score' => $score,
                        'rank' => $attemptIndex + 1,
                        'is_selected' => $attemptIndex === 0,
                    ]);
                    $output = $run->outputs()->create(['output_path' => $imagePath, 'status' => 'completed', 'metadata' => ['demo' => true, 'ai_evaluation' => ['model' => 'openai/gpt-4o-mini', 'summary' => 'امتیاز نمونه برای نمایش روند رتبه‌بندی آزمایشگاه.'] ], 'manual_score' => $score, 'note' => 'نمره‌ی نمونه برای بررسی رابط آزمایشگاه.', 'is_winner' => $attemptIndex === 0]);
                    foreach (['prompt_adherence' => $score, 'visual_quality' => min(5, $score + .1), 'composition' => max(1, $score - .1), 'product_fit' => $score] as $criterion => $criterionScore) {
                        LabScore::create(['lab_run_output_id' => $output->id, 'admin_id' => null, 'evaluator_type' => 'ai', 'criterion' => $criterion, 'score' => (int) round($criterionScore), 'note' => 'امتیاز نمونه']);
                    }
                }

                $selectedRun = $experiment->runs()->where('grade_key', $gradeIndex)->where('is_selected', true)->latest('id')->first();
                $gradeConfig[$gradeIndex] = [
                    'key' => $gradeIndex,
                    'label' => $grade['label'],
                    'resolution' => $grade['resolution'],
                    'aspect_ratio' => $grade['aspect_ratio'],
                    'primary' => ['run_id' => $selectedRun?->id, 'model_id' => $selectedRun?->model_id, 'provider' => $selectedRun?->provider, 'name' => $selectedRun?->alias, 'score' => $selectedRun?->final_score, 'rank' => 1],
                    'fallbacks' => $experiment->runs()->where('grade_key', $gradeIndex)->where('is_selected', false)->get()->map(fn (LabRun $run) => ['run_id' => $run->id, 'model_id' => $run->model_id, 'provider' => $run->provider, 'name' => $run->alias, 'score' => $run->final_score, 'rank' => $run->rank])->values()->all(),
                ];
            }

            $experiment->forceFill(['overall_score' => collect($experiment->runs()->pluck('final_score'))->avg(), 'settings' => array_merge((array) $experiment->settings, ['grades' => $gradeConfig])])->save();
            $product->forceFill(['lab_grade_config' => $gradeConfig])->save();
            LabAuditLog::updateOrCreate(['lab_experiment_id' => $experiment->id, 'action' => 'demo_seeded'], ['metadata' => ['source' => 'LabDemoSeeder']]);
        }
    }
}
