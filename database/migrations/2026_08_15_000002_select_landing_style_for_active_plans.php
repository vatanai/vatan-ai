<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('plans')->where('status', 'active')->whereNull('archived_at')->update(['card_style' => 'landing']);
    }

    public function down(): void
    {
        DB::table('plans')->where('card_style', 'landing')->update(['card_style' => 'classic']);
    }
};
