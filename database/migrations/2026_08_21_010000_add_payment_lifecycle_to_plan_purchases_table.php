<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plan_purchases')) {
            return;
        }

        Schema::table('plan_purchases', function (Blueprint $table): void {
            if (! Schema::hasColumn('plan_purchases', 'order_number')) {
                $table->string('order_number')->nullable()->unique();
            }
            if (! Schema::hasColumn('plan_purchases', 'gateway')) {
                $table->string('gateway', 30)->nullable();
            }
            if (! Schema::hasColumn('plan_purchases', 'gateway_track_id')) {
                $table->string('gateway_track_id')->nullable()->unique();
            }
            if (! Schema::hasColumn('plan_purchases', 'gateway_reference')) {
                $table->string('gateway_reference')->nullable();
            }
            if (! Schema::hasColumn('plan_purchases', 'gateway_status')) {
                $table->string('gateway_status')->nullable();
            }
            if (! Schema::hasColumn('plan_purchases', 'billing_name')) {
                $table->string('billing_name')->nullable();
            }
            if (! Schema::hasColumn('plan_purchases', 'billing_email')) {
                $table->string('billing_email')->nullable();
            }
            if (! Schema::hasColumn('plan_purchases', 'billing_phone')) {
                $table->string('billing_phone', 20)->nullable();
            }
            if (! Schema::hasColumn('plan_purchases', 'failure_reason')) {
                $table->text('failure_reason')->nullable();
            }
            if (! Schema::hasColumn('plan_purchases', 'callback_payload')) {
                $table->json('callback_payload')->nullable();
            }
            if (! Schema::hasColumn('plan_purchases', 'initiated_at')) {
                $table->timestamp('initiated_at')->nullable();
            }
            if (! Schema::hasColumn('plan_purchases', 'verified_at')) {
                $table->timestamp('verified_at')->nullable();
            }
            if (! Schema::hasColumn('plan_purchases', 'failed_at')) {
                $table->timestamp('failed_at')->nullable();
            }
            if (! Schema::hasColumn('plan_purchases', 'expired_at')) {
                $table->timestamp('expired_at')->nullable();
            }
        });

        DB::table('plan_purchases')->whereNull('order_number')->orderBy('id')->each(function (object $purchase): void {
            DB::table('plan_purchases')->where('id', $purchase->id)->update([
                'order_number' => 'PLN-LEGACY-' . $purchase->id,
                'gateway' => $purchase->status === 'completed' ? 'legacy' : null,
                'verified_at' => $purchase->status === 'completed' ? ($purchase->purchased_at ?: $purchase->created_at) : null,
                'initiated_at' => $purchase->created_at,
                'updated_at' => now(),
            ]);
        });

        Schema::table('plan_purchases', function (Blueprint $table): void {
            $table->index(['status', 'initiated_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('plan_purchases')) {
            return;
        }

        Schema::table('plan_purchases', function (Blueprint $table): void {
            $table->dropIndex(['status', 'initiated_at']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropUnique(['order_number']);
            $table->dropUnique(['gateway_track_id']);
            $table->dropColumn([
                'order_number', 'gateway', 'gateway_track_id', 'gateway_reference', 'gateway_status',
                'billing_name', 'billing_email', 'billing_phone', 'failure_reason', 'callback_payload',
                'initiated_at', 'verified_at', 'failed_at', 'expired_at',
            ]);
        });
    }
};
