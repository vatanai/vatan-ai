<?php

namespace App\Services\SmartInstagram;

use App\Models\SmartInstagram\AiProfile;

/** تشخیص پیام‌های حساس بر اساس پروفایل فعال همان فضای کاری. */
class SensitiveMessageDetector
{
    public function __construct(private readonly WorkspaceContext $context)
    {
    }

    public function detects(string $text): bool
    {
        if (trim($text) === '') {
            return false;
        }

        $keywords = (array) (AiProfile::query()
            ->where('workspace_id', $this->context->id())
            ->where('is_active', true)
            ->value('escalation_keywords') ?? []);

        if (is_string($keywords)) {
            $keywords = (array) json_decode($keywords, true);
        }

        foreach ($keywords as $keyword) {
            if (PersianText::containsKeyword($text, (string) $keyword)) {
                return true;
            }
        }

        return false;
    }
}
