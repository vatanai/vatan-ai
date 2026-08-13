<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_models')) {
            Schema::table('ai_models', function (Blueprint $table): void {
                if (! Schema::hasColumn('ai_models', 'lab_categories')) {
                    $table->json('lab_categories')->nullable()->after('recommended_category_ids');
                }
                if (! Schema::hasColumn('ai_models', 'lab_priority')) {
                    $table->unsignedInteger('lab_priority')->default(999)->after('lab_categories');
                }
                if (! Schema::hasColumn('ai_models', 'featured_in_lab')) {
                    $table->boolean('featured_in_lab')->default(false)->after('lab_priority');
                }
                if (! Schema::hasColumn('ai_models', 'lab_status')) {
                    $table->string('lab_status', 24)->default('active')->after('featured_in_lab');
                }
                if (! Schema::hasColumn('ai_models', 'lab_description')) {
                    $table->text('lab_description')->nullable()->after('lab_status');
                }
            });

            $metadata = [
                ['match' => ['nano-banana-pro', 'nano banana pro', 'gemini-3-pro-image'], 'categories' => ['popular', 'identity', 'vip'], 'priority' => 1, 'status' => 'active', 'description' => 'کیفیت ممتاز، انتقال صحنه و حفظ هویت برای خروجی‌های حساس و VIP.'],
                ['match' => ['nano-banana-2', 'nano banana 2', 'gemini-3.1-flash-image'], 'categories' => ['popular', 'identity', 'economic'], 'priority' => 2, 'status' => 'active', 'description' => 'تعادل مناسب بین کیفیت، سرعت و هزینه برای استفاده روزمره.'],
                ['match' => ['pulid'], 'categories' => ['popular', 'identity'], 'priority' => 3, 'status' => 'active', 'description' => 'مدل هویت‌محور برای بیشترین شباهت چهره در خروجی.'],
                ['match' => ['seedream'], 'categories' => ['popular', 'economic'], 'priority' => 4, 'status' => 'active', 'description' => 'مناسب انتقال نور، رنگ، محیط و ترکیب‌بندی با هزینه کنترل‌شده.'],
                ['match' => ['flux.2-max', 'flux 2 max', 'flux-2-max', 'flux_2_max'], 'categories' => ['popular', 'vip'], 'priority' => 5, 'status' => 'active', 'description' => 'خروجی فتورئال با کیفیت بالا و پشتیبانی از چند تصویر مرجع.'],
                ['match' => ['gpt-image', 'gpt image'], 'categories' => ['popular', 'vip'], 'priority' => 6, 'status' => 'active', 'description' => 'ادیت پیچیده، پیروی دقیق از دستور و رندر بهتر متن داخل تصویر.'],
                ['match' => ['photomaker'], 'categories' => ['identity', 'experimental'], 'priority' => 7, 'status' => 'experimental', 'description' => 'گزینه آزمایشی برای حفظ هویت با چند تصویر مرجع.'],
                ['match' => ['instant-id', 'instant id', 'instantid'], 'categories' => ['identity', 'experimental'], 'priority' => 8, 'status' => 'experimental', 'description' => 'پایپ‌لاین آزمایشی حفظ هویت چهره برای مقایسه در آزمایشگاه.'],
            ];

            foreach ($metadata as $item) {
                $models = DB::table('ai_models')->get(['id', 'name', 'openrouter_model_id', 'external_model_id']);
                foreach ($models as $model) {
                    $searchable = strtolower(implode(' ', array_filter([
                        (string) ($model->name ?? ''),
                        (string) ($model->openrouter_model_id ?? ''),
                        (string) ($model->external_model_id ?? ''),
                    ])));
                    if (! collect($item['match'])->contains(fn (string $pattern) => str_contains($searchable, strtolower($pattern)))) continue;
                    DB::table('ai_models')->where('id', $model->id)->update([
                        'lab_categories' => json_encode($item['categories'], JSON_UNESCAPED_UNICODE),
                        'lab_priority' => $item['priority'],
                        'featured_in_lab' => true,
                        'lab_status' => $item['status'],
                        'lab_description' => $item['description'],
                    ]);
                }
            }
        }

        if (Schema::hasTable('lab_runs')) {
            Schema::table('lab_runs', function (Blueprint $table): void {
                if (! Schema::hasColumn('lab_runs', 'quality_score')) $table->decimal('quality_score', 5, 2)->nullable()->after('final_score');
                if (! Schema::hasColumn('lab_runs', 'identity_score')) $table->decimal('identity_score', 5, 2)->nullable()->after('quality_score');
                if (! Schema::hasColumn('lab_runs', 'sample_match_score')) $table->decimal('sample_match_score', 5, 2)->nullable()->after('identity_score');
                if (! Schema::hasColumn('lab_runs', 'cost')) $table->decimal('cost', 12, 6)->nullable()->after('actual_cost_usd');
                if (! Schema::hasColumn('lab_runs', 'latency_ms')) $table->unsignedBigInteger('latency_ms')->nullable()->after('duration_ms');
                if (! Schema::hasColumn('lab_runs', 'notes')) $table->text('notes')->nullable()->after('error_message');
            });
        }

        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'model_configuration')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->json('model_configuration')->nullable()->after('lab_grade_config');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'model_configuration')) {
            Schema::table('products', fn (Blueprint $table) => $table->dropColumn('model_configuration'));
        }
        if (Schema::hasTable('lab_runs')) {
            Schema::table('lab_runs', function (Blueprint $table): void {
                $columns = ['quality_score', 'identity_score', 'sample_match_score', 'cost', 'latency_ms', 'notes'];
                $existing = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('lab_runs', $column)));
                if ($existing) $table->dropColumn($existing);
            });
        }
        if (Schema::hasTable('ai_models')) {
            Schema::table('ai_models', function (Blueprint $table): void {
                $columns = ['lab_categories', 'lab_priority', 'featured_in_lab', 'lab_status', 'lab_description'];
                $existing = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('ai_models', $column)));
                if ($existing) $table->dropColumn($existing);
            });
        }
    }
};
