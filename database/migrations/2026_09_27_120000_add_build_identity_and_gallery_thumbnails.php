<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->uuid('build_uuid')->nullable()->unique()->after('order_number');
        });
        Schema::table('ai_provider_requests', function (Blueprint $table): void {
            $table->uuid('build_uuid')->nullable()->index()->after('order_id');
        });
        Schema::table('generated_images', function (Blueprint $table): void {
            $table->uuid('build_uuid')->nullable()->index()->after('ai_provider_request_id');
        });
        Schema::table('generated_videos', function (Blueprint $table): void {
            $table->uuid('build_uuid')->nullable()->index()->after('ai_provider_request_id');
        });
        Schema::table('lab_runs', function (Blueprint $table): void {
            $table->uuid('build_uuid')->nullable()->unique()->after('id');
        });
        Schema::table('user_gallery_items', function (Blueprint $table): void {
            $table->foreignId('order_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->uuid('build_uuid')->nullable()->index()->after('order_id');
            $table->string('thumbnail_path')->nullable()->after('preview_path');
            $table->string('thumbnail_mime_type', 120)->nullable()->after('preview_mime_type');
            $table->index(['order_id', 'source_type'], 'gallery_order_source_idx');
        });
        Schema::table('service_credit_transactions', function (Blueprint $table): void {
            $table->index('occurred_at', 'credit_occurred_at_idx');
        });

        DB::table('orders')->orderBy('id')->chunkById(500, function ($orders): void {
            foreach ($orders as $order) {
                DB::table('orders')->where('id', $order->id)->update([
                    'build_uuid' => $this->stableUuid('order', (int) $order->id),
                ]);
            }
        });

        $orderBuilds = DB::table('orders')->pluck('build_uuid', 'id');

        DB::table('ai_provider_requests')->orderBy('id')->chunkById(500, function ($requests) use ($orderBuilds): void {
            foreach ($requests as $request) {
                DB::table('ai_provider_requests')->where('id', $request->id)->update([
                    'build_uuid' => $request->order_id && isset($orderBuilds[$request->order_id])
                        ? $orderBuilds[$request->order_id]
                        : $this->stableUuid('provider-request', (int) $request->id),
                ]);
            }
        });

        $requestBuilds = DB::table('ai_provider_requests')->pluck('build_uuid', 'id');
        $this->backfillGeneratedMedia('generated_images', 'image', $orderBuilds, $requestBuilds);
        $this->backfillGeneratedMedia('generated_videos', 'video', $orderBuilds, $requestBuilds);

        $imageBuilds = DB::table('generated_images')->pluck('build_uuid', 'id');
        $videoBuilds = DB::table('generated_videos')->pluck('build_uuid', 'id');
        $imageOrders = DB::table('generated_images')->whereNotNull('order_id')->pluck('order_id', 'id');
        $videoOrders = DB::table('generated_videos')->whereNotNull('order_id')->pluck('order_id', 'id');
        DB::table('user_gallery_items')->orderBy('id')->chunkById(500, function ($items) use ($orderBuilds, $imageBuilds, $videoBuilds, $imageOrders, $videoOrders): void {
            foreach ($items as $item) {
                $metadata = is_string($item->metadata) ? json_decode($item->metadata, true) : (array) $item->metadata;
                $orderId = isset($metadata['order_id']) ? (int) $metadata['order_id'] : null;
                $orderId = $orderId && isset($orderBuilds[$orderId]) ? $orderId : null;
                $buildUuid = $orderId && isset($orderBuilds[$orderId]) ? $orderBuilds[$orderId] : null;

                if (! $buildUuid && $item->source_type === 'output_image' && $item->source_id) {
                    $orderId = isset($imageOrders[$item->source_id]) ? (int) $imageOrders[$item->source_id] : $orderId;
                    $buildUuid = $imageBuilds[$item->source_id] ?? null;
                }
                if (! $buildUuid && $item->source_type === 'output_video' && $item->source_id) {
                    $orderId = isset($videoOrders[$item->source_id]) ? (int) $videoOrders[$item->source_id] : $orderId;
                    $buildUuid = $videoBuilds[$item->source_id] ?? null;
                }
                if (! $buildUuid && $item->source_id && in_array($item->source_type, ['upload', 'input_image', 'input_text', 'input_video'], true)) {
                    $orderId = isset($orderBuilds[$item->source_id]) ? (int) $item->source_id : $orderId;
                    $buildUuid = $orderId ? ($orderBuilds[$orderId] ?? null) : null;
                }

                DB::table('user_gallery_items')->where('id', $item->id)->update([
                    'order_id' => $orderId ?: null,
                    'build_uuid' => $buildUuid ?: $this->stableUuid('gallery-item', (int) $item->id),
                ]);
            }
        });

        DB::table('lab_runs')->orderBy('id')->chunkById(500, function ($runs): void {
            foreach ($runs as $run) {
                DB::table('lab_runs')->where('id', $run->id)->update([
                    'build_uuid' => $this->stableUuid('lab-run', (int) $run->id),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_credit_transactions', function (Blueprint $table): void {
            $table->dropIndex('credit_occurred_at_idx');
        });
        Schema::table('user_gallery_items', function (Blueprint $table): void {
            $table->dropIndex('gallery_order_source_idx');
            $table->dropIndex(['build_uuid']);
            $table->dropConstrainedForeignId('order_id');
            $table->dropColumn(['build_uuid', 'thumbnail_path', 'thumbnail_mime_type']);
        });
        Schema::table('lab_runs', function (Blueprint $table): void {
            $table->dropUnique(['build_uuid']);
            $table->dropColumn('build_uuid');
        });
        foreach (['generated_videos', 'generated_images', 'ai_provider_requests'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropIndex(['build_uuid']);
                $table->dropColumn('build_uuid');
            });
        }
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['build_uuid']);
            $table->dropColumn('build_uuid');
        });
    }

    private function backfillGeneratedMedia(string $table, string $namespace, $orderBuilds, $requestBuilds): void
    {
        DB::table($table)->orderBy('id')->chunkById(500, function ($items) use ($table, $namespace, $orderBuilds, $requestBuilds): void {
            foreach ($items as $item) {
                $buildUuid = $item->order_id ? ($orderBuilds[$item->order_id] ?? null) : null;
                $buildUuid ??= $item->ai_provider_request_id ? ($requestBuilds[$item->ai_provider_request_id] ?? null) : null;
                DB::table($table)->where('id', $item->id)->update([
                    'build_uuid' => $buildUuid ?: $this->stableUuid($namespace, (int) $item->id),
                ]);
            }
        });
    }

    private function stableUuid(string $namespace, int $id): string
    {
        $hex = sha1('vatan-ai:'.$namespace.':'.$id);

        return sprintf(
            '%s-%s-5%s-%s%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 13, 3),
            dechex((hexdec($hex[16]) & 0x3) | 0x8),
            substr($hex, 17, 3),
            substr($hex, 20, 12),
        );
    }
};
