<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_cases', function (Blueprint $table): void {
            $table->id();
            $table->string('case_number', 50)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('anchor_plan_purchase_id')->nullable()->unique()->constrained('plan_purchases')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('status', 20)->default('open')->index();
            $table->boolean('is_test')->default(false)->index();
            $table->unsignedInteger('opening_balance')->default(0);
            $table->timestamp('started_at')->index();
            $table->timestamp('ended_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'started_at']);
        });

        Schema::create('finance_credit_lots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('finance_case_id')->nullable()->constrained('finance_cases')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plan_purchase_id')->nullable()->constrained('plan_purchases')->nullOnDelete();
            $table->foreignId('token_log_id')->nullable()->constrained('token_logs')->nullOnDelete();
            $table->string('source_key')->unique();
            $table->string('source_type', 40)->index();
            $table->string('source_label')->nullable();
            $table->unsignedInteger('credits_granted')->default(0);
            $table->unsignedInteger('credits_remaining')->default(0);
            $table->decimal('revenue_toman', 20, 2)->default(0);
            $table->boolean('is_promotional')->default(false)->index();
            $table->timestamp('occurred_at')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'credits_remaining', 'occurred_at'], 'finance_credit_lots_user_balance');
        });

        Schema::create('finance_credit_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('finance_credit_lot_id')->constrained('finance_credit_lots')->cascadeOnDelete();
            $table->foreignId('finance_case_id')->nullable()->constrained('finance_cases')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reservation_key', 60)->index();
            $table->unsignedInteger('credits_reserved')->default(0);
            $table->unsignedInteger('credits_used')->default(0);
            $table->unsignedInteger('credits_refunded')->default(0);
            $table->decimal('revenue_toman', 20, 2)->default(0);
            $table->string('status', 20)->default('reserved')->index();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('settled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
        });

        Schema::create('finance_case_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('finance_case_id')->nullable()->constrained('finance_cases')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plan_purchase_id')->nullable()->constrained('plan_purchases')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('token_log_id')->nullable()->constrained('token_logs')->nullOnDelete();
            $table->string('source_key')->unique();
            $table->string('event_type', 40)->index();
            $table->string('source_type', 40)->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('credits_delta')->default(0);
            $table->unsignedInteger('balance_after')->nullable();
            $table->decimal('amount_usd', 16, 6)->nullable();
            $table->decimal('amount_toman', 20, 2)->nullable();
            $table->string('data_quality', 20)->default('actual')->index();
            $table->timestamp('occurred_at')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'occurred_at']);
            $table->index(['finance_case_id', 'occurred_at'], 'finance_case_events_case_time');
        });

        Schema::table('generated_images', function (Blueprint $table): void {
            if (! Schema::hasColumn('generated_images', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('generated_images', 'ai_provider_request_id')) {
                $table->foreignId('ai_provider_request_id')->nullable()->after('order_id')->constrained('ai_provider_requests')->nullOnDelete();
            }
        });

        Schema::table('finance_exchange_rates', function (Blueprint $table): void {
            if (! Schema::hasColumn('finance_exchange_rates', 'rate_to_toman')) {
                $table->decimal('rate_to_toman', 20, 4)->nullable()->after('rate_to_irr');
            }
        });

        Schema::table('finance_transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('finance_transactions', 'exchange_rate_toman')) {
                $table->decimal('exchange_rate_toman', 20, 4)->nullable()->after('exchange_rate_irr');
            }
            if (! Schema::hasColumn('finance_transactions', 'amount_toman')) {
                $table->decimal('amount_toman', 20, 2)->nullable()->after('amount_irr');
            }
        });

        Schema::table('finance_plan_snapshots', function (Blueprint $table): void {
            foreach ([
                'gross_sales_toman', 'received_toman', 'gateway_fee_toman',
                'estimated_model_cost_toman', 'allocated_infrastructure_toman',
                'allocated_workforce_toman', 'gross_profit_toman', 'net_profit_toman',
            ] as $column) {
                if (! Schema::hasColumn('finance_plan_snapshots', $column)) {
                    $table->decimal($column, 20, 2)->nullable();
                }
            }
        });

        Schema::table('finance_order_snapshots', function (Blueprint $table): void {
            foreach ([
                'exchange_rate_toman' => [20, 4],
                'estimated_cost_toman' => [20, 2],
                'actual_cost_toman' => [20, 2],
                'direct_cost_toman' => [20, 2],
                'allocated_revenue_toman' => [20, 2],
                'allocated_infrastructure_toman' => [20, 2],
                'allocated_workforce_toman' => [20, 2],
                'gross_profit_toman' => [20, 2],
                'net_profit_toman' => [20, 2],
            ] as $column => [$precision, $scale]) {
                if (! Schema::hasColumn('finance_order_snapshots', $column)) {
                    $table->decimal($column, $precision, $scale)->nullable();
                }
            }
            if (! Schema::hasColumn('finance_order_snapshots', 'cost_quality')) {
                $table->string('cost_quality', 20)->default('missing')->index();
            }
            if (! Schema::hasColumn('finance_order_snapshots', 'actual_request_count')) {
                $table->unsignedInteger('actual_request_count')->default(0);
            }
            if (! Schema::hasColumn('finance_order_snapshots', 'estimated_request_count')) {
                $table->unsignedInteger('estimated_request_count')->default(0);
            }
            if (! Schema::hasColumn('finance_order_snapshots', 'missing_cost_request_count')) {
                $table->unsignedInteger('missing_cost_request_count')->default(0);
            }
        });

        DB::table('finance_exchange_rates')->whereNull('rate_to_toman')->update([
            'rate_to_toman' => DB::raw('rate_to_irr / 10'),
        ]);
        DB::table('finance_transactions')->whereNull('exchange_rate_toman')->update([
            'exchange_rate_toman' => DB::raw("CASE WHEN currency = 'USD' THEN exchange_rate_irr / 10 ELSE 1 END"),
            'amount_toman' => DB::raw("CASE WHEN currency = 'USD' THEN amount_irr / 10 WHEN source_key LIKE 'plan-purchase:%' THEN amount_irr ELSE amount_irr / 10 END"),
        ]);
        DB::table('finance_plan_snapshots')->update([
            'gross_sales_toman' => DB::raw('gross_sales_irr'),
            'received_toman' => DB::raw('received_irr'),
            'gateway_fee_toman' => DB::raw('gateway_fee_irr'),
            'estimated_model_cost_toman' => DB::raw('estimated_model_cost_irr / 10'),
            'allocated_infrastructure_toman' => DB::raw('allocated_infrastructure_irr'),
            'allocated_workforce_toman' => DB::raw('allocated_workforce_irr'),
            'gross_profit_toman' => DB::raw('gross_sales_irr - gateway_fee_irr - (estimated_model_cost_irr / 10)'),
            'net_profit_toman' => DB::raw('gross_sales_irr - gateway_fee_irr - (estimated_model_cost_irr / 10) - allocated_infrastructure_irr - allocated_workforce_irr'),
        ]);
        DB::table('finance_order_snapshots')->update([
            'exchange_rate_toman' => DB::raw('exchange_rate_irr / 10'),
            'estimated_cost_toman' => DB::raw('estimated_cost_irr / 10'),
            'actual_cost_toman' => DB::raw('actual_cost_irr / 10'),
            'direct_cost_toman' => DB::raw('direct_cost_irr / 10'),
            'allocated_revenue_toman' => DB::raw('allocated_revenue_irr'),
            'allocated_infrastructure_toman' => DB::raw('allocated_infrastructure_irr'),
            'allocated_workforce_toman' => DB::raw('allocated_workforce_irr'),
            'gross_profit_toman' => DB::raw('allocated_revenue_irr - (direct_cost_irr / 10)'),
            'net_profit_toman' => DB::raw('allocated_revenue_irr - (direct_cost_irr / 10) - allocated_infrastructure_irr - allocated_workforce_irr'),
        ]);

        $legacyCostPerCredit = DB::table('finance_settings')->where('key', 'estimated_model_cost_per_credit_irr')->value('value');
        $legacyCostPerCredit = is_string($legacyCostPerCredit) ? json_decode($legacyCostPerCredit, true) : $legacyCostPerCredit;
        DB::table('finance_settings')->updateOrInsert(
            ['key' => 'estimated_model_cost_per_credit_toman'],
            [
                'value' => json_encode($legacyCostPerCredit !== null ? ((float) $legacyCostPerCredit / 10) : 100),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        Schema::table('generated_images', function (Blueprint $table): void {
            if (Schema::hasColumn('generated_images', 'ai_provider_request_id')) {
                $table->dropConstrainedForeignId('ai_provider_request_id');
            }
            if (Schema::hasColumn('generated_images', 'order_id')) {
                $table->dropConstrainedForeignId('order_id');
            }
        });

        Schema::dropIfExists('finance_case_events');
        Schema::dropIfExists('finance_credit_allocations');
        Schema::dropIfExists('finance_credit_lots');
        Schema::dropIfExists('finance_cases');

        Schema::table('finance_order_snapshots', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                'exchange_rate_toman', 'estimated_cost_toman', 'actual_cost_toman', 'direct_cost_toman',
                'allocated_revenue_toman', 'allocated_infrastructure_toman', 'allocated_workforce_toman',
                'gross_profit_toman', 'net_profit_toman', 'cost_quality', 'actual_request_count',
                'estimated_request_count', 'missing_cost_request_count',
            ], fn (string $column) => Schema::hasColumn('finance_order_snapshots', $column)));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
        Schema::table('finance_plan_snapshots', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                'gross_sales_toman', 'received_toman', 'gateway_fee_toman', 'estimated_model_cost_toman',
                'allocated_infrastructure_toman', 'allocated_workforce_toman', 'gross_profit_toman', 'net_profit_toman',
            ], fn (string $column) => Schema::hasColumn('finance_plan_snapshots', $column)));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
        Schema::table('finance_transactions', function (Blueprint $table): void {
            $columns = array_values(array_filter(['exchange_rate_toman', 'amount_toman'], fn (string $column) => Schema::hasColumn('finance_transactions', $column)));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
        Schema::table('finance_exchange_rates', function (Blueprint $table): void {
            if (Schema::hasColumn('finance_exchange_rates', 'rate_to_toman')) {
                $table->dropColumn('rate_to_toman');
            }
        });
        DB::table('finance_settings')->where('key', 'estimated_model_cost_per_credit_toman')->delete();
    }
};
