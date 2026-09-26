<?php

use App\Models\SalesPartnerStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_partner_stages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('stage')->unique();
            $table->string('title', 160);
            $table->string('description', 500)->nullable();
            $table->string('icon', 80)->nullable();
            $table->text('goal')->nullable();
            $table->text('task')->nullable();
            $table->text('script')->nullable();
            $table->text('follow_up')->nullable();
            $table->unsignedSmallInteger('default_follow_up_hours')->nullable();
            $table->unsignedTinyInteger('max_follow_ups')->default(0);
            $table->string('message_type', 20)->default('message');
            $table->text('advance_when')->nullable();
            $table->text('stop_when')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'stage']);
        });

        $now = now();
        $rows = array_map(static fn (array $definition): array => $definition + [
            'is_active' => true,
            'updated_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], SalesPartnerStage::defaultDefinitions());

        foreach ($rows as $row) {
            SalesPartnerStage::query()->create($row);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_partner_stages');
    }
};
