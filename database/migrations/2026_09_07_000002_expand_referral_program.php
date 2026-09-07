<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('referral_settings')) {
            Schema::table('referral_settings', function (Blueprint $table): void {
                if (! Schema::hasColumn('referral_settings', 'referral_discount_percent')) {
                    $table->decimal('referral_discount_percent', 5, 2)->unsigned()->default(10)->after('reward_trigger');
                }
                if (! Schema::hasColumn('referral_settings', 'purchase_commission_percent')) {
                    $table->decimal('purchase_commission_percent', 5, 2)->unsigned()->default(10)->after('referral_discount_percent');
                }
                if (! Schema::hasColumn('referral_settings', 'minimum_purchase_amount')) {
                    $table->unsignedBigInteger('minimum_purchase_amount')->nullable()->after('purchase_commission_percent');
                }
            });

            DB::table('referral_settings')
                ->where('reward_trigger', 'first_purchase')
                ->where('inviter_reward_tokens', 5)
                ->update([
                    'reward_trigger' => 'registration',
                    'profile_description' => 'لینک اختصاصی خودت را در شبکه‌های اجتماعی یا برای دوستانت بفرست. هر کاربر جدیدی که با لینک تو ثبت‌نام کند، دعوت موفق ثبت می‌شود و پاداش توکنی‌ات خودکار به حسابت می‌آید.',
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('referral_links') === false) {
            Schema::create('referral_links', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('inviter_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->string('slug', 32)->unique();
                $table->string('destination_url', 2048);
                $table->string('status', 20)->default('active')->index();
                $table->timestamp('deactivated_at')->nullable();
                $table->timestamps();
                $table->index(['inviter_id', 'status']);
                $table->index(['product_id', 'status']);
            });
        }

        if (Schema::hasTable('referral_visits')) {
            Schema::table('referral_visits', function (Blueprint $table): void {
                if (! Schema::hasColumn('referral_visits', 'link_id')) {
                    $table->foreignId('link_id')->nullable()->constrained('referral_links')->nullOnDelete()->after('inviter_id');
                }
                if (! Schema::hasColumn('referral_visits', 'product_id')) {
                    $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete()->after('link_id');
                }
                if (! Schema::hasColumn('referral_visits', 'source')) {
                    $table->string('source', 30)->default('link')->after('product_id');
                }
                foreach (['utm_source', 'utm_medium', 'utm_campaign'] as $column) {
                    if (! Schema::hasColumn('referral_visits', $column)) {
                        $table->string($column, 120)->nullable()->after('source');
                    }
                }
                $table->index(['link_id', 'visited_at']);
                $table->index(['product_id', 'visited_at']);
            });
        }

        if (Schema::hasTable('referral_conversions')) {
            Schema::table('referral_conversions', function (Blueprint $table): void {
                if (! Schema::hasColumn('referral_conversions', 'link_id')) {
                    $table->foreignId('link_id')->nullable()->constrained('referral_links')->nullOnDelete()->after('visit_id');
                }
                if (! Schema::hasColumn('referral_conversions', 'product_id')) {
                    $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete()->after('link_id');
                }
                if (! Schema::hasColumn('referral_conversions', 'first_image_at')) {
                    $table->timestamp('first_image_at')->nullable()->after('qualified_at');
                }
                if (! Schema::hasColumn('referral_conversions', 'first_purchase_at')) {
                    $table->timestamp('first_purchase_at')->nullable()->after('first_image_at');
                }
                if (! Schema::hasColumn('referral_conversions', 'purchase_amount')) {
                    $table->unsignedBigInteger('purchase_amount')->default(0)->after('first_purchase_at');
                }
                if (! Schema::hasColumn('referral_conversions', 'discount_amount')) {
                    $table->unsignedBigInteger('discount_amount')->default(0)->after('purchase_amount');
                }
                if (! Schema::hasColumn('referral_conversions', 'commission_amount')) {
                    $table->unsignedBigInteger('commission_amount')->default(0)->after('discount_amount');
                }
            });
        }

        if (Schema::hasTable('referral_rewards')) {
            Schema::table('referral_rewards', function (Blueprint $table): void {
                if (! Schema::hasColumn('referral_rewards', 'currency')) {
                    $table->string('currency', 12)->default('token')->after('reward_type');
                }
                if (! Schema::hasColumn('referral_rewards', 'plan_purchase_id')) {
                    $table->foreignId('plan_purchase_id')->nullable()->constrained('plan_purchases')->nullOnDelete()->after('conversion_id');
                }
            });
        }

        if (Schema::hasTable('plan_purchases')) {
            Schema::table('plan_purchases', function (Blueprint $table): void {
                if (! Schema::hasColumn('plan_purchases', 'original_amount')) {
                    $table->unsignedBigInteger('original_amount')->nullable()->after('paid_amount');
                }
                if (! Schema::hasColumn('plan_purchases', 'discount_amount')) {
                    $table->unsignedBigInteger('discount_amount')->default(0)->after('original_amount');
                }
                if (! Schema::hasColumn('plan_purchases', 'referral_conversion_id')) {
                    $table->foreignId('referral_conversion_id')->nullable()->constrained('referral_conversions')->nullOnDelete()->after('discount_amount');
                }
                if (! Schema::hasColumn('plan_purchases', 'referral_snapshot')) {
                    $table->json('referral_snapshot')->nullable()->after('referral_conversion_id');
                }
            });
        }

        if (Schema::hasTable('referral_events') === false) {
            Schema::create('referral_events', function (Blueprint $table): void {
                $table->id();
                $table->uuid('event_uuid')->unique();
                $table->string('event_type', 50)->index();
                $table->string('event_key', 180)->unique();
                $table->foreignId('inviter_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('invitee_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('link_id')->nullable()->constrained('referral_links')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->foreignId('conversion_id')->nullable()->constrained('referral_conversions')->nullOnDelete();
                $table->foreignId('plan_purchase_id')->nullable()->constrained('plan_purchases')->nullOnDelete();
                $table->string('source', 30)->nullable();
                $table->string('currency', 12)->nullable();
                $table->unsignedBigInteger('amount')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('occurred_at');
                $table->timestamps();
                $table->index(['inviter_id', 'event_type', 'occurred_at']);
                $table->index(['invitee_id', 'event_type', 'occurred_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_events');
        // داده‌های لینک و رویداد عمداً در rollback حذف نمی‌شوند؛ این migration فقط در محیط توسعه قابل rollback است.
    }
};
