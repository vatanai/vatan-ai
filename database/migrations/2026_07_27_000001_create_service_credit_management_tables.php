<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('service_credit_accounts')) {
            Schema::create('service_credit_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('currency', 3)->default('USD');
                $table->decimal('manual_balance', 18, 6)->default(0);
                $table->decimal('low_balance_threshold', 18, 6)->default(0);
                $table->boolean('show_on_dashboard')->default(true);
                $table->boolean('is_active')->default(true);
                $table->string('sync_driver')->default('manual');
                $table->timestamp('last_synced_at')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('service_credit_transactions')) {
            Schema::create('service_credit_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_credit_account_id')->constrained()->cascadeOnDelete();
                $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
                $table->string('type', 16);
                $table->decimal('amount', 18, 6);
                $table->dateTime('occurred_at');
                $table->string('reference')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
                $table->index(['service_credit_account_id', 'occurred_at'], 'credit_account_time_idx');
            });
        }

        if (DB::table('service_credit_accounts')->count() === 0) {
            DB::table('service_credit_accounts')->insert([
                [
                    'name' => 'OpenRouter',
                    'slug' => 'openrouter',
                    'currency' => 'USD',
                    'sync_driver' => 'openrouter',
                    'show_on_dashboard' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Liara',
                    'slug' => 'liara',
                    'currency' => 'IRR',
                    'sync_driver' => 'manual',
                    'show_on_dashboard' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_credit_transactions');
        Schema::dropIfExists('service_credit_accounts');
    }
};
