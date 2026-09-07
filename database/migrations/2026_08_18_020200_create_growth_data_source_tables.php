<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('growth_data_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->string('source_type', 40)->index();
            $table->string('channel', 40)->nullable()->index();
            $table->string('ingestion_method', 40)->index();
            $table->json('data_types')->nullable();
            $table->text('config')->nullable();
            $table->string('connection_status', 30)->default('active')->index();
            $table->string('health_status', 30)->default('idle')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_system')->default(false);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_received_at')->nullable();
            $table->text('last_error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('growth_data_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('growth_data_source_id')->constrained('growth_data_sources')->cascadeOnDelete();
            $table->string('source_field');
            $table->string('growth_metric', 60)->index();
            $table->string('transform', 30)->default('integer');
            $table->boolean('is_primary')->default(false)->index();
            $table->unsignedSmallInteger('fallback_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['growth_data_source_id', 'source_field'], 'growth_mapping_source_field_unique');
        });

        Schema::create('growth_raw_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('growth_data_source_id')->constrained('growth_data_sources')->cascadeOnDelete();
            $table->string('external_id')->nullable()->index();
            $table->string('record_type', 50)->index();
            $table->json('payload');
            $table->json('normalized_data')->nullable();
            $table->string('normalization_status', 30)->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('received_at')->index();
            $table->timestamp('normalized_at')->nullable();
            $table->timestamps();
        });

        Schema::create('growth_content_daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('growth_content_id')->constrained('growth_contents')->cascadeOnDelete();
            $table->foreignId('growth_data_source_id')->nullable()->constrained('growth_data_sources')->nullOnDelete();
            $table->foreignId('growth_raw_record_id')->nullable()->constrained('growth_raw_records')->nullOnDelete();
            $table->date('metric_date')->index();
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('engagements')->default(0);
            $table->unsignedBigInteger('comments')->default(0);
            $table->unsignedBigInteger('shares')->default(0);
            $table->unsignedBigInteger('likes')->default(0);
            $table->unsignedBigInteger('saves')->default(0);
            $table->string('entry_mode', 30)->default('manual')->index();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['growth_content_id', 'growth_data_source_id', 'metric_date'],
                'growth_content_source_day_unique'
            );
        });

        Schema::table('growth_contents', function (Blueprint $table) {
            $table->unsignedBigInteger('likes')->default(0)->after('shares');
            $table->unsignedBigInteger('saves')->default(0)->after('likes');
            $table->timestamp('metrics_updated_at')->nullable()->after('published_at');
        });

        $now = now();
        DB::table('growth_data_sources')->insert([
            [
                'id' => 1,
                'name' => 'هسته وطن', 'slug' => 'watan-core', 'source_type' => 'internal',
                'channel' => 'website', 'ingestion_method' => 'database',
                'data_types' => json_encode(['users', 'generations', 'purchases', 'plans', 'credit_usage']),
                'connection_status' => 'connected', 'health_status' => 'healthy',
                'is_active' => true, 'is_system' => true, 'priority' => 10,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'رهگیر لینک رشد', 'slug' => 'growth-link-tracker', 'source_type' => 'internal_tracking',
                'channel' => 'website', 'ingestion_method' => 'tracking',
                'data_types' => json_encode(['clicks', 'unique_clicks', 'page_opens', 'referrer', 'device', 'operating_system', 'browser', 'country', 'city', 'occurred_at', 'visitor_status', 'channel', 'content_id', 'link_id']),
                'connection_status' => 'connected', 'health_status' => 'healthy',
                'is_active' => true, 'is_system' => true, 'priority' => 10,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'ورود دستی اینستاگرام', 'slug' => 'instagram-manual', 'source_type' => 'manual',
                'channel' => 'instagram', 'ingestion_method' => 'manual',
                'data_types' => json_encode(['views', 'engagements', 'comments', 'shares', 'likes', 'saves']),
                'connection_status' => 'active', 'health_status' => 'idle',
                'is_active' => true, 'is_system' => false, 'priority' => 50,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 4,
                'name' => 'ورود دستی تلگرام', 'slug' => 'telegram-manual', 'source_type' => 'manual',
                'channel' => 'telegram', 'ingestion_method' => 'manual',
                'data_types' => json_encode(['views', 'engagements', 'comments', 'shares', 'likes', 'saves']),
                'connection_status' => 'active', 'health_status' => 'idle',
                'is_active' => true, 'is_system' => false, 'priority' => 50,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 5,
                'name' => 'ورود دستی یوتیوب', 'slug' => 'youtube-manual', 'source_type' => 'manual',
                'channel' => 'youtube', 'ingestion_method' => 'manual',
                'data_types' => json_encode(['views', 'engagements', 'comments', 'shares', 'likes', 'saves']),
                'connection_status' => 'active', 'health_status' => 'idle',
                'is_active' => true, 'is_system' => false, 'priority' => 50,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 6,
                'name' => 'ورود دستی سایر کانال‌ها', 'slug' => 'other-manual', 'source_type' => 'manual',
                'channel' => 'other', 'ingestion_method' => 'manual',
                'data_types' => json_encode(['views', 'engagements', 'comments', 'shares', 'likes', 'saves']),
                'connection_status' => 'active', 'health_status' => 'idle',
                'is_active' => true, 'is_system' => false, 'priority' => 50,
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        $sourceIds = collect([
            'watan-core' => 1,
            'growth-link-tracker' => 2,
            'instagram-manual' => 3,
            'telegram-manual' => 4,
            'youtube-manual' => 5,
            'other-manual' => 6,
        ]);
        $mappings = [
            'growth-link-tracker' => [
                ['event_type', 'clicks', 'text', true, 1],
                ['visitor_id', 'unique_clicks', 'text', true, 1],
                ['parent_event_uuid', 'page_opens', 'text', true, 1],
                ['referrer', 'referrer', 'text', true, 1],
                ['device_type', 'device', 'text', true, 1],
                ['operating_system', 'operating_system', 'text', true, 1],
                ['browser', 'browser', 'text', true, 1],
            ],
            'instagram-manual' => [
                ['views', 'views', 'integer', true, 1], ['engagements', 'engagements', 'integer', true, 1],
                ['comments', 'comments', 'integer', true, 1], ['shares', 'shares', 'integer', true, 1],
                ['likes', 'likes', 'integer', true, 1], ['saves', 'saves', 'integer', true, 1],
            ],
            'telegram-manual' => [
                ['views', 'views', 'integer', true, 1], ['engagements', 'engagements', 'integer', true, 1],
                ['comments', 'comments', 'integer', true, 1], ['shares', 'shares', 'integer', true, 1],
                ['likes', 'likes', 'integer', true, 1], ['saves', 'saves', 'integer', true, 1],
            ],
            'youtube-manual' => [
                ['views', 'views', 'integer', true, 1], ['engagements', 'engagements', 'integer', true, 1],
                ['comments', 'comments', 'integer', true, 1], ['shares', 'shares', 'integer', true, 1],
                ['likes', 'likes', 'integer', true, 1], ['saves', 'saves', 'integer', true, 1],
            ],
            'other-manual' => [
                ['views', 'views', 'integer', true, 1], ['engagements', 'engagements', 'integer', true, 1],
                ['comments', 'comments', 'integer', true, 1], ['shares', 'shares', 'integer', true, 1],
                ['likes', 'likes', 'integer', true, 1], ['saves', 'saves', 'integer', true, 1],
            ],
        ];

        foreach ($mappings as $sourceSlug => $sourceMappings) {
            foreach ($sourceMappings as [$sourceField, $metric, $transform, $primary, $fallback]) {
                DB::table('growth_data_mappings')->insert([
                    'growth_data_source_id' => $sourceIds[$sourceSlug],
                    'source_field' => $sourceField,
                    'growth_metric' => $metric,
                    'transform' => $transform,
                    'is_primary' => $primary,
                    'fallback_order' => $fallback,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('growth_content_daily_metrics');
        Schema::dropIfExists('growth_raw_records');
        Schema::dropIfExists('growth_data_mappings');
        Schema::dropIfExists('growth_data_sources');
        Schema::table('growth_contents', function (Blueprint $table) {
            $table->dropColumn(['likes', 'saves', 'metrics_updated_at']);
        });
    }
};
