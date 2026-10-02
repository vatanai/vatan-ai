<?php

namespace App\Services\SmartInstagram\Ai;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use ZipArchive;

/** استخراج متن خوانا از فایل‌های دانش (txt, md, csv, json, html, docx) — بدون وابستگی جدید. */
class KnowledgeExtractor
{
    public function fromUpload(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'txt');
        $path = $file->getRealPath();
        if (!$path || !is_readable($path)) {
            throw new RuntimeException('فایل قابل خواندن نیست.');
        }

        $text = match ($extension) {
            'docx' => $this->docx($path),
            'csv' => $this->csv($path),
            'json' => $this->json((string) file_get_contents($path)),
            'html', 'htm' => $this->html((string) file_get_contents($path)),
            default => (string) file_get_contents($path),
        };

        $text = $this->clean($text);
        if (mb_strlen($text) < 10) {
            throw new RuntimeException('متن قابل‌استفاده‌ای در فایل پیدا نشد.');
        }

        return $text;
    }

    public function clean(string $text): string
    {
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8, Windows-1256, ISO-8859-1');
        }
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text) ?? $text; // BOM
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[^\S\n]+/u', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function docx(string $path): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('افزونه‌ی Zip روی سرور فعال نیست.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('فایل Word معتبر نیست.');
        }
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === '') {
            throw new RuntimeException('محتوای فایل Word خوانده نشد.');
        }

        $xml = str_replace(['</w:p>', '<w:br/>', '<w:tab/>', '</w:tr>'], ["\n", "\n", "\t", "\n"], $xml);
        $xml = str_replace('</w:tc>', ' | ', $xml);

        return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function csv(string $path): string
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new RuntimeException('فایل CSV خوانده نشد.');
        }
        $header = null;
        $lines = [];
        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false && count($lines) < 5000) {
            $row = array_map(fn ($cell) => trim((string) $cell), $row);
            if ($header === null) {
                $header = $row;
                continue;
            }
            $pairs = [];
            foreach ($row as $i => $cell) {
                if ($cell !== '') {
                    $pairs[] = ($header[$i] ?? 'ستون '.($i + 1)).': '.$cell;
                }
            }
            if ($pairs) {
                $lines[] = implode(' | ', $pairs);
            }
        }
        fclose($handle);

        return implode("\n", $lines);
    }

    private function json(string $raw): string
    {
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new RuntimeException('فایل JSON معتبر نیست.');
        }
        $lines = [];
        $walk = function ($value, string $prefix) use (&$walk, &$lines): void {
            if (is_array($value)) {
                foreach ($value as $key => $item) {
                    $walk($item, $prefix === '' ? (string) $key : $prefix.' › '.$key);
                }

                return;
            }
            $lines[] = ($prefix !== '' ? $prefix.': ' : '').(is_bool($value) ? ($value ? 'بله' : 'خیر') : (string) $value);
        };
        $walk($data, '');

        return implode("\n", $lines);
    }

    private function html(string $html): string
    {
        $html = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#<(br|/p|/div|/li|/h[1-6]|/tr)[^>]*>#i', "\n", $html) ?? $html;

        return html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
