<?php

use Database\Seeders\ReplicateCuratedModelSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_models')) {
            (new ReplicateCuratedModelSeeder())->run();
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('ai_models')) return;

        \App\Models\AiModel::query()
            ->where('provider', 'replicate')
            ->whereIn('external_model_id', [
                'zsxkib/instant-id',
                'lucataco/modelscope-facefusion',
                'tencentarc/photomaker',
                'black-forest-labs/flux-dev',
                'black-forest-labs/flux-schnell',
                'lucataco/realvisxl-v2.0',
                'lucataco/real-esrgan',
                'sunfjun/stable-video-diffusion',
            ])
            ->delete();
    }
};
