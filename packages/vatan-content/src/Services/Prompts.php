<?php

namespace Vatan\Content\Services;

use Vatan\Content\Models\PromptVersion;

/** پرامپت فعال هر لایه + جایگذاری متغیرها ({{name}}) */
class Prompts
{
    public function active(string $layer): ?PromptVersion
    {
        $ws = (int) config('content-factory.workspace_id', 1);

        return PromptVersion::where('workspace_id', $ws)->where('layer', $layer)->where('status', 'active')->orderByDesc('version')->first()
            ?? PromptVersion::where('workspace_id', $ws)->where('layer', $layer)->orderByDesc('version')->first();
    }

    /** @return array{system:string,user:string,version:?int} */
    public function render(string $layer, array $vars): array
    {
        $p = $this->active($layer);
        $body = (string) ($p->body ?? '');
        [$system, $user] = array_pad(preg_split('/^-{3,}\s*USER\s*-{3,}\s*$/mu', $body, 2), 2, '');
        $fill = fn (string $t) => preg_replace_callback('/\{\{\s*([a-z_\.]+)\s*\}\}/u', fn ($m) => is_array($v = data_get($vars, $m[1])) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) ($v ?? ''), $t);
        if ($p) {
            $p->increment('use_count');
        }

        return ['system' => trim($fill($system)), 'user' => trim($fill($user)) ?: json_encode($vars, JSON_UNESCAPED_UNICODE), 'version' => $p?->version];
    }
}
