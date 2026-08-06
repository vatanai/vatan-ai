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
            $columns = Schema::getColumnListing('plans');

            if (!in_array('billing_type', $columns, true)) {
                $table->string('billing_type', 30)->default('monthly')->after('price');
            }
            if (!in_array('price_prefix', $columns, true)) {
                $table->string('price_prefix', 30)->nullable()->after('billing_type');
            }
            if (!in_array('compare_at_price', $columns, true)) {
                $table->unsignedBigInteger('compare_at_price')->nullable()->after('price_prefix');
            }
            if (!in_array('token_label', $columns, true)) {
                $table->string('token_label')->nullable()->after('tokens');
            }
            if (!in_array('is_unlimited', $columns, true)) {
                $table->boolean('is_unlimited')->default(false)->after('token_label');
            }
            if (!in_array('audience_overrides', $columns, true)) {
                $table->json('audience_overrides')->nullable()->after('features');
            }
            if (!in_array('is_featured', $columns, true)) {
                $table->boolean('is_featured')->default(false)->after('sort_order');
            }
            if (!in_array('purchase_limit', $columns, true)) {
                $table->unsignedInteger('purchase_limit')->nullable()->after('is_featured');
            }
            if (!in_array('starts_at', $columns, true)) {
                $table->timestamp('starts_at')->nullable()->after('purchase_limit');
            }
            if (!in_array('ends_at', $columns, true)) {
                $table->timestamp('ends_at')->nullable()->after('starts_at');
            }
            if (!in_array('archived_at', $columns, true)) {
                $table->timestamp('archived_at')->nullable()->after('ends_at');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'customer_segment')) {
                $table->string('customer_segment', 30)->default('regular')->after('status')->index();
            }
        });

        if (!Schema::hasTable('plan_settings')) {
            Schema::create('plan_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->json('value')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('plan_purchases')) {
            Schema::create('plan_purchases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
                $table->string('plan_code')->nullable();
                $table->string('plan_name');
                $table->string('customer_segment', 30)->default('regular');
                $table->unsignedBigInteger('paid_amount')->default(0);
                $table->unsignedInteger('granted_tokens')->default(0);
                $table->json('plan_snapshot');
                $table->string('status', 30)->default('completed');
                $table->string('payment_reference')->nullable()->unique();
                $table->timestamp('purchased_at');
                $table->timestamps();
                $table->index(['user_id', 'status']);
            });
        }

        DB::table('plan_settings')->updateOrInsert(
            ['key' => 'display'],
            [
                'value' => json_encode([
                    'mode' => 'cards',
                    'home_limit' => 3,
                    'show_images' => false,
                    'show_comparison' => true,
                    'title' => 'پلن مناسب خودت را انتخاب کن',
                    'subtitle' => 'از شروع رایگان تا راهکارهای سازمانی، متناسب با میزان استفاده شما',
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_purchases');
        Schema::dropIfExists('plan_settings');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'customer_segment')) {
                $table->dropIndex(['customer_segment']);
                $table->dropColumn('customer_segment');
            }
        });

        Schema::table('plans', function (Blueprint $table) {
            $columns = array_values(array_filter([
                'billing_type', 'price_prefix', 'compare_at_price', 'token_label',
                'is_unlimited', 'audience_overrides', 'is_featured', 'purchase_limit',
                'starts_at', 'ends_at', 'archived_at',
            ], fn (string $column) => Schema::hasColumn('plans', $column)));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
