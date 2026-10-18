<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Vatan\Content\Models\PromptVersion;

/*
| v0.3 — افزودن لایه‌ی «پرامپت جامع ساخت پست» (و هر لایه‌ی پایه‌ی تازه) بدون دست زدن به ویرایش‌های ادمین.
*/
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cf_prompt_versions')) {
            return;
        }
        $file = dirname(__DIR__).'/seed/prompts.json';
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
        $ws = (int) config('content-factory.workspace_id', 1);
        foreach ($data['prompts'] ?? [] as $p) {
            if (! PromptVersion::where('workspace_id', $ws)->where('layer', $p['layer'])->exists()) {
                PromptVersion::create(['workspace_id' => $ws, 'layer' => $p['layer'], 'title' => $p['title'], 'version' => 1, 'body' => $p['body'],
                    'status' => 'active', 'editor' => 'Claude', 'notes' => $p['notes'] ?? null]);
            }
        }
    }

    public function down(): void
    {
    }
};
