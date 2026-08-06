<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('service_credit_snapshots')) {
            Schema::create('service_credit_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_credit_account_id')->constrained()->cascadeOnDelete();
                $table->decimal('balance', 18, 6);
                $table->string('currency', 3);
                $table->dateTime('captured_at');
                $table->timestamps();
                $table->index(
                    ['service_credit_account_id', 'captured_at'],
                    'credit_snapshot_account_time_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_credit_snapshots');
    }
};
