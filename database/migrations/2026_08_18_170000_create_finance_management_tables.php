<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_exchange_rates', function (Blueprint $table): void {
            $table->id();
            $table->string('currency', 3)->default('USD');
            $table->date('rate_date');
            $table->decimal('rate_to_irr', 20, 4);
            $table->string('source')->nullable();
            $table->boolean('is_manual')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
            $table->unique(['currency', 'rate_date']);
        });

        Schema::create('finance_cost_centers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('finance_vendors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('tax_id', 80)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('finance_payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('finance_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('finance_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_code', 40)->unique();
            $table->string('direction', 20)->index();
            $table->string('category', 50)->index();
            $table->string('title');
            $table->decimal('amount_original', 20, 4);
            $table->string('currency', 3)->default('IRR');
            $table->decimal('exchange_rate_irr', 20, 4)->default(1);
            $table->decimal('amount_irr', 20, 2);
            $table->string('status', 30)->default('draft')->index();
            $table->timestamp('occurred_at')->index();
            $table->date('due_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('cost_center_id')->nullable()->constrained('finance_cost_centers')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('finance_vendors')->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('finance_payment_methods')->nullOnDelete();
            $table->string('invoice_path')->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->string('source_type', 80)->default('manual');
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->string('source_key')->nullable()->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('plan_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('plan_purchase_id')->nullable()->index();
            $table->unsignedBigInteger('ai_model_id')->nullable()->index();
            $table->string('provider', 50)->nullable()->index();
            $table->string('acquisition_channel', 50)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['direction', 'status', 'occurred_at'], 'finance_tx_direction_status_time');
        });

        Schema::create('finance_plan_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('plan_purchase_id')->unique();
            $table->unsignedBigInteger('plan_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('plan_code')->nullable();
            $table->string('plan_name');
            $table->string('model_tier_key', 40)->nullable()->index();
            $table->unsignedBigInteger('granted_credits')->default(0);
            $table->decimal('gross_sales_irr', 20, 2)->default(0);
            $table->decimal('received_irr', 20, 2)->default(0);
            $table->decimal('gateway_fee_irr', 20, 2)->default(0);
            $table->decimal('estimated_model_cost_irr', 20, 2)->default(0);
            $table->decimal('allocated_infrastructure_irr', 20, 2)->default(0);
            $table->decimal('allocated_workforce_irr', 20, 2)->default(0);
            $table->decimal('gross_profit_irr', 20, 2)->default(0);
            $table->decimal('net_profit_irr', 20, 2)->default(0);
            $table->decimal('margin_percent', 8, 2)->default(0);
            $table->string('acquisition_channel', 50)->nullable()->index();
            $table->string('formula_version', 20)->default('v1');
            $table->json('source_snapshot');
            $table->timestamp('purchased_at')->index();
            $table->timestamp('captured_at');
            $table->timestamps();
        });

        Schema::create('finance_order_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id')->unique();
            $table->unsignedBigInteger('plan_purchase_id')->nullable()->index();
            $table->unsignedBigInteger('plan_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('order_number')->nullable();
            $table->string('plan_name')->nullable();
            $table->string('model_tier_key', 40)->nullable()->index();
            $table->string('product_name')->nullable();
            $table->string('primary_model')->nullable()->index();
            $table->json('fallback_models')->nullable();
            $table->string('actual_model')->nullable()->index();
            $table->string('provider', 50)->nullable()->index();
            $table->unsignedBigInteger('credits_used')->default(0);
            $table->decimal('allocated_revenue_irr', 20, 2)->default(0);
            $table->decimal('estimated_cost_usd', 16, 6)->default(0);
            $table->decimal('actual_cost_usd', 16, 6)->default(0);
            $table->decimal('exchange_rate_irr', 20, 4)->default(0);
            $table->decimal('estimated_cost_irr', 20, 2)->default(0);
            $table->decimal('actual_cost_irr', 20, 2)->default(0);
            $table->decimal('direct_cost_irr', 20, 2)->default(0);
            $table->decimal('allocated_infrastructure_irr', 20, 2)->default(0);
            $table->decimal('allocated_workforce_irr', 20, 2)->default(0);
            $table->decimal('gross_profit_irr', 20, 2)->default(0);
            $table->decimal('net_profit_irr', 20, 2)->default(0);
            $table->decimal('margin_percent', 8, 2)->default(0);
            $table->string('acquisition_channel', 50)->nullable()->index();
            $table->string('formula_version', 20)->default('v1');
            $table->json('source_snapshot');
            $table->timestamp('ordered_at')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('captured_at');
            $table->timestamps();
        });

        Schema::create('finance_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('action', 40)->index();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id'], 'finance_audit_subject');
        });

        $now = now();
        DB::table('finance_cost_centers')->insert([
            ['name' => 'هوش مصنوعی', 'code' => 'ai', 'description' => 'هزینه مستقیم مدل‌ها و ارائه‌دهنده‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'زیرساخت', 'code' => 'infrastructure', 'description' => 'سرور، هاست و دامنه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'نیروی انسانی', 'code' => 'workforce', 'description' => 'حقوق و هزینه تیم', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'بازاریابی', 'code' => 'marketing', 'description' => 'تبلیغات و جذب کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'فروش و مالی', 'code' => 'sales-finance', 'description' => 'فروش، درگاه و عملیات مالی', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'عمومی', 'code' => 'general', 'description' => 'سایر موارد', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('finance_payment_methods')->insert([
            ['name' => 'درگاه آنلاین', 'code' => 'gateway', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'انتقال بانکی', 'code' => 'bank-transfer', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'نقدی', 'code' => 'cash', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'اعتبار پلتفرم', 'code' => 'platform-credit', 'created_at' => $now, 'updated_at' => $now],
        ]);

        foreach (config('finance.setting_defaults', []) as $key => $value) {
            DB::table('finance_settings')->insert([
                'key' => $key,
                'value' => json_encode($value),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_audit_logs');
        Schema::dropIfExists('finance_order_snapshots');
        Schema::dropIfExists('finance_plan_snapshots');
        Schema::dropIfExists('finance_transactions');
        Schema::dropIfExists('finance_settings');
        Schema::dropIfExists('finance_payment_methods');
        Schema::dropIfExists('finance_vendors');
        Schema::dropIfExists('finance_cost_centers');
        Schema::dropIfExists('finance_exchange_rates');
    }
};
