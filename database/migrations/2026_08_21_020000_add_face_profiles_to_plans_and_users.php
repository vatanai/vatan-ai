<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plans') && ! Schema::hasColumn('plans', 'face_profile_limit')) {
            Schema::table('plans', function (Blueprint $table): void {
                $table->unsignedTinyInteger('face_profile_limit')->default(0)->after('tokens');
            });
        }

        if (! Schema::hasTable('face_profiles')) {
            Schema::create('face_profiles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name', 80);
                $table->json('reference_images');
                $table->string('status', 20)->default('active');
                $table->timestamps();
                $table->index(['user_id', 'status']);
            });
        }

        if (! Schema::hasTable('plans')) {
            return;
        }

        $limits = [
            'vatan-gift' => 0,
            'vatan-professional' => 1,
            'vatan-advanced' => 4,
            'vatan-business' => 10,
        ];

        foreach ($limits as $slug => $limit) {
            $plan = DB::table('plans')->where('slug', $slug)->first();
            if (! $plan) {
                continue;
            }

            $features = json_decode((string) ($plan->features ?? '[]'), true);
            $features = is_array($features) ? $features : [];
            $features = collect($features)
                ->reject(fn ($feature) => trim((string) (is_array($feature) ? ($feature['title'] ?? '') : $feature)) === 'پروفایل چهره')
                ->values()
                ->all();
            $features[] = [
                'title' => 'پروفایل چهره',
                'value' => (string) $limit,
                'included' => $limit > 0 ? 'limited' : 'no',
                'highlighted' => true,
                'sort_order' => count($features) + 1,
            ];

            DB::table('plans')->where('id', $plan->id)->update([
                'face_profile_limit' => $limit,
                'features' => json_encode($features, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('face_profiles');

        if (Schema::hasTable('plans') && Schema::hasColumn('plans', 'face_profile_limit')) {
            Schema::table('plans', function (Blueprint $table): void {
                $table->dropColumn('face_profile_limit');
            });
        }
    }
};
