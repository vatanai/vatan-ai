<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('instagram_outbound_messages', 'message_payload')) {
            Schema::table('instagram_outbound_messages', function (Blueprint $table): void {
                $table->json('message_payload')->nullable()->after('body');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('instagram_outbound_messages', 'message_payload')) {
            Schema::table('instagram_outbound_messages', function (Blueprint $table): void {
                $table->dropColumn('message_payload');
            });
        }
    }
};
