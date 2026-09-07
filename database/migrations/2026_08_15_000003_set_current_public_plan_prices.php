<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();

        DB::table('plans')->where('slug', 'free')->update([
            'status' => 'inactive',
            'updated_at' => $now,
        ]);

        foreach ([
            'start' => ['price' => 10_000, 'sort_order' => 1],
            'pro' => ['price' => 15_000, 'sort_order' => 2],
            'premium' => ['price' => 20_000, 'sort_order' => 3],
            'business' => ['price' => 25_000, 'sort_order' => 4],
        ] as $slug => $values) {
            DB::table('plans')->where('slug', $slug)->update(array_merge($values, [
                'status' => 'active',
                'archived_at' => null,
                'card_style' => 'landing',
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('plans')->where('slug', 'free')->update(['status' => 'active']);
        DB::table('plans')->where('slug', 'business')->update(['status' => 'inactive']);
    }
};
