<?php

namespace App\Services\SmartInstagram\Gateways;

/** تبدیل پاسخ insights متا به آرایه‌ی ساده؛ معیار دریافت‌نشده null می‌ماند (نه صفر جعلی). */
final class InsightsParser
{
    /** @return array{saved:?int,shares:?int,reach:?int} */
    public static function parse(array $rows): array
    {
        $out = ['saved' => null, 'shares' => null, 'reach' => null];
        foreach ($rows as $row) {
            $name = (string) ($row['name'] ?? '');
            if (!array_key_exists($name, $out)) {
                continue;
            }
            $value = data_get($row, 'values.0.value', data_get($row, 'total_value.value'));
            $out[$name] = is_numeric($value) ? (int) $value : null;
        }

        return $out;
    }
}
