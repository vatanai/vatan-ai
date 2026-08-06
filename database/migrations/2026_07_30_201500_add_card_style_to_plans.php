<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'card_style')) {
                $table->string('card_style', 30)->default('classic')->after('icon');
            }
        });

        $styles = [
            'free' => 'minimal',
            'start' => 'classic',
            'pro' => 'featured',
            'premium' => 'premium',
            'business' => 'business',
            'enterprise' => 'bold',
        ];

        foreach ($styles as $slug => $style) {
            DB::table('plans')->where('slug', $slug)->update([
                'card_style' => $style,
                'status' => in_array($slug, ['free', 'start', 'pro', 'premium'], true) ? 'active' : 'inactive',
                'updated_at' => now(),
            ]);
        }

        DB::table('plan_settings')->where('key', 'display')->update([
            'value' => json_encode([
                'mode' => 'cards',
                'home_limit' => 4,
                'show_images' => false,
                'show_comparison' => true,
                'title' => 'پلن مناسب خودت را انتخاب کن',
                'subtitle' => 'از شروع رایگان تا پلن‌های حرفه‌ای، متناسب با میزان استفاده شما',
            ], JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'card_style')) {
                $table->dropColumn('card_style');
            }
        });
    }
};
