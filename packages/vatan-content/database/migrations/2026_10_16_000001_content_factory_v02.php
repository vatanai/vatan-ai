<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| کارخانه‌ی محتوا v0.2 — صف کارهای ایجنت مک، جزئیات کامل تقویم، اتصال اینستاگرام هوشمند و امتیازدهی.
*/
return new class extends Migration
{
    private function add(string $table, string $col, callable $def): void
    {
        if (Schema::hasTable($table) && ! Schema::hasColumn($table, $col)) {
            Schema::table($table, fn (Blueprint $t) => $def($t));
        }
    }

    public function up(): void
    {
        if (! Schema::hasTable('cf_jobs')) {
            Schema::create('cf_jobs', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('workspace_id')->default(1)->index();
                $t->string('type', 30)->index();
                $t->string('location', 10)->default('mac');
                $t->string('status', 20)->default('queued')->index();
                $t->unsignedBigInteger('production_id')->nullable()->index();
                $t->json('payload')->nullable();
                $t->json('result')->nullable();
                $t->unsignedTinyInteger('progress')->default(0);
                $t->string('message')->nullable();
                $t->longText('log')->nullable();
                $t->string('agent')->nullable();
                $t->unsignedTinyInteger('attempts')->default(0);
                $t->string('created_by')->nullable();
                $t->timestamp('claimed_at')->nullable();
                $t->timestamp('finished_at')->nullable();
                $t->timestamps();
            });
        }

        $items = [
            'product_type' => fn ($t) => $t->string('product_type', 20)->nullable(),
            'product_id' => fn ($t) => $t->unsignedBigInteger('product_id')->nullable(),
            'product_key' => fn ($t) => $t->string('product_key', 120)->nullable(),
            'product_label' => fn ($t) => $t->string('product_label')->nullable(),
            'product_url' => fn ($t) => $t->string('product_url')->nullable(),
            'style_id' => fn ($t) => $t->unsignedBigInteger('style_id')->nullable(),
            'music_id' => fn ($t) => $t->unsignedBigInteger('music_id')->nullable(),
            'engine' => fn ($t) => $t->string('engine', 40)->nullable(),
            'build_location' => fn ($t) => $t->string('build_location', 10)->default('mac'),
            'template' => fn ($t) => $t->string('template', 60)->nullable(),
            'duration_sec' => fn ($t) => $t->unsignedSmallInteger('duration_sec')->nullable(),
            'scenes' => fn ($t) => $t->json('scenes')->nullable(),
            'story_specs' => fn ($t) => $t->json('story_specs')->nullable(),
            'cover_text' => fn ($t) => $t->string('cover_text')->nullable(),
            'caption' => fn ($t) => $t->longText('caption')->nullable(),
            'hashtags' => fn ($t) => $t->text('hashtags')->nullable(),
            'keywords' => fn ($t) => $t->json('keywords')->nullable(),
            'visual_prompt' => fn ($t) => $t->text('visual_prompt')->nullable(),
            'ai_plan' => fn ($t) => $t->json('ai_plan')->nullable(),
            'assets' => fn ($t) => $t->json('assets')->nullable(),
            'audience' => fn ($t) => $t->string('audience')->nullable(),
            'experiment' => fn ($t) => $t->string('experiment')->nullable(),
        ];
        foreach ($items as $col => $def) {
            $this->add('cf_calendar_items', $col, $def);
        }

        $prods = [
            'si_post_id' => fn ($t) => $t->unsignedBigInteger('si_post_id')->nullable(),
            'si_campaign_id' => fn ($t) => $t->unsignedBigInteger('si_campaign_id')->nullable(),
            'auto_reply' => fn ($t) => $t->boolean('auto_reply')->default(true),
            'keywords' => fn ($t) => $t->json('keywords')->nullable(),
            'scores' => fn ($t) => $t->json('scores')->nullable(),
            'score_total' => fn ($t) => $t->decimal('score_total', 6, 2)->nullable(),
            'score_confidence' => fn ($t) => $t->string('score_confidence', 20)->nullable(),
            'scored_at' => fn ($t) => $t->timestamp('scored_at')->nullable(),
            'product_type' => fn ($t) => $t->string('product_type', 20)->nullable(),
            'product_id' => fn ($t) => $t->unsignedBigInteger('product_id')->nullable(),
            'product_label' => fn ($t) => $t->string('product_label')->nullable(),
            'product_url' => fn ($t) => $t->string('product_url')->nullable(),
        ];
        foreach ($prods as $col => $def) {
            $this->add('cf_productions', $col, $def);
        }

        $styles = [
            'template' => fn ($t) => $t->string('template', 60)->nullable(),
            'recipe' => fn ($t) => $t->json('recipe')->nullable(),
            'palette' => fn ($t) => $t->json('palette')->nullable(),
            'fonts' => fn ($t) => $t->json('fonts')->nullable(),
            'folder' => fn ($t) => $t->string('folder')->nullable(),
            'sort' => fn ($t) => $t->unsignedInteger('sort')->default(0),
        ];
        foreach ($styles as $col => $def) {
            $this->add('cf_styles', $col, $def);
        }
        $this->add('cf_references', 'moved_path', fn ($t) => $t->string('moved_path')->nullable());
        $this->add('cf_references', 'style_confidence', fn ($t) => $t->string('style_confidence', 20)->nullable());
        $this->add('cf_music', 'library_path', fn ($t) => $t->string('library_path')->nullable());
        $this->add('cf_music', 'folder', fn ($t) => $t->string('folder')->nullable());
        $this->add('cf_insights', 'kind', fn ($t) => $t->string('kind', 30)->default('learning'));
        $this->add('cf_insights', 'scope', fn ($t) => $t->string('scope')->nullable());
        $this->add('cf_metric_snapshots', 'source', fn ($t) => $t->string('source', 30)->nullable());
    }

    public function down(): void
    {
        Schema::dropIfExists('cf_jobs');
    }
};
