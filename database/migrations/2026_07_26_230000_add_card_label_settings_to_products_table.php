<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('card_label_enabled')->default(false)->after('card_label');
            $table->string('card_label_position', 20)->default('top-right')->after('card_label_enabled');
            $table->json('fallback_model_providers')->nullable()->after('fallback_models');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['card_label_enabled', 'card_label_position', 'fallback_model_providers']);
        });
    }
};
