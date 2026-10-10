<?php

namespace Vatan\Seo\Support;

class Playbook
{
    public static function path(string $file): string
    {
        return dirname(__DIR__, 2).'/playbook/'.$file.'.php';
    }

    public static function infrastructure(): array { return require self::path('infrastructure'); }
    public static function goals(): array { return require self::path('goals'); }
    public static function scenarios(): array { return require self::path('scenarios'); }
    public static function roadmap(): array { return require self::path('roadmap'); }

    public static function find(string $key): ?array
    {
        foreach (self::infrastructure() as $item) {
            if ($item['key'] === $key) return $item + ['pillar' => 'infrastructure'];
        }
        $goals = self::goals();
        foreach (['setup', 'per_keyword', 'recurring'] as $group) {
            foreach ($goals[$group] ?? [] as $item) {
                if ($item['key'] === $key) return $item + ['pillar' => 'goals'];
            }
        }
        $r = self::roadmap();
        foreach (['weekly', 'daily', 'milestones', 'cycle'] as $group) {
            foreach ($r[$group] as $item) {
                if ($item['key'] === $key) return $item;
            }
        }
        return null;
    }
}
