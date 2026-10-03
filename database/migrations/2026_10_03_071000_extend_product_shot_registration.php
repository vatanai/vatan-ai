<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تنظیمات تکمیلی مسیر مستقل محصولات پروداکتی.
 * تمام تغییرها افزودنی‌اند و مسیر محصولات پرتره‌ای هیچ وابستگی به آن‌ها ندارد.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('occupations')) {
            Schema::create('occupations', function (Blueprint $table): void {
                $table->id();
                $table->string('name_fa');
                $table->string('name_en')->nullable();
                $table->string('slug')->unique();
                $table->string('group_key', 60)->default('other')->index();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('occupation_product')) {
            Schema::create('occupation_product', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('occupation_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->timestamps();
                $table->unique(['occupation_id', 'product_id']);
            });
        }

        Schema::table('product_shots', function (Blueprint $table): void {
            if (! Schema::hasColumn('product_shots', 'prompt_override')) {
                $table->text('prompt_override')->nullable();
            }
            if (! Schema::hasColumn('product_shots', 'model_configuration')) {
                $table->json('model_configuration')->nullable();
            }
            if (! Schema::hasColumn('product_shots', 'allowed_aspect_ratios')) {
                $table->json('allowed_aspect_ratios')->nullable();
            }
            if (! Schema::hasColumn('product_shots', 'aspect_ratio_default')) {
                $table->string('aspect_ratio_default', 10)->nullable();
            }
            if (! Schema::hasColumn('product_shots', 'aspect_ratio_user_selectable')) {
                $table->boolean('aspect_ratio_user_selectable')->default(true);
            }
            if (! Schema::hasColumn('product_shots', 'options_enabled')) {
                $table->json('options_enabled')->nullable();
            }
        });

        Schema::table('shot_batches', function (Blueprint $table): void {
            if (! Schema::hasColumn('shot_batches', 'quality_level')) {
                $table->string('quality_level', 20)->default('standard')->index();
            }
            if (! Schema::hasColumn('shot_batches', 'product_sheet_path')) {
                $table->string('product_sheet_path')->nullable();
            }
        });

        Schema::table('shot_batch_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('shot_batch_items', 'aspect_ratio')) {
                $table->string('aspect_ratio', 10)->default('4:5');
            }
            if (! Schema::hasColumn('shot_batch_items', 'quality_level')) {
                $table->string('quality_level', 20)->default('standard');
            }
        });

        Schema::table('product_shot_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('product_shot_settings', 'preflight_prompt')) {
                $table->text('preflight_prompt')->nullable();
            }
            if (! Schema::hasColumn('product_shot_settings', 'preflight_blocking_issues')) {
                $table->json('preflight_blocking_issues')->nullable();
            }
            if (! Schema::hasColumn('product_shot_settings', 'preflight_min_side')) {
                $table->unsignedSmallInteger('preflight_min_side')->default(900);
            }
            if (! Schema::hasColumn('product_shot_settings', 'product_sheet_enabled')) {
                $table->boolean('product_sheet_enabled')->default(true);
            }
            if (! Schema::hasColumn('product_shot_settings', 'product_sheet_size')) {
                $table->unsignedSmallInteger('product_sheet_size')->default(2048);
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_shot_settings', function (Blueprint $table): void {
            foreach (['preflight_prompt', 'preflight_blocking_issues', 'preflight_min_side', 'product_sheet_enabled', 'product_sheet_size'] as $column) {
                if (Schema::hasColumn('product_shot_settings', $column)) $table->dropColumn($column);
            }
        });
        Schema::table('shot_batch_items', function (Blueprint $table): void {
            foreach (['aspect_ratio', 'quality_level'] as $column) {
                if (Schema::hasColumn('shot_batch_items', $column)) $table->dropColumn($column);
            }
        });
        Schema::table('shot_batches', function (Blueprint $table): void {
            foreach (['quality_level', 'product_sheet_path'] as $column) {
                if (Schema::hasColumn('shot_batches', $column)) $table->dropColumn($column);
            }
        });
        Schema::table('product_shots', function (Blueprint $table): void {
            foreach (['prompt_override', 'model_configuration', 'allowed_aspect_ratios', 'aspect_ratio_default', 'aspect_ratio_user_selectable', 'options_enabled'] as $column) {
                if (Schema::hasColumn('product_shots', $column)) $table->dropColumn($column);
            }
        });
        Schema::dropIfExists('occupation_product');
        Schema::dropIfExists('occupations');
    }
};
