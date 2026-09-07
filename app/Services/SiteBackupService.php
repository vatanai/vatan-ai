<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class SiteBackupService
{
    private const DIRECTORY = 'backups/site';
    private const FORMAT = 'vatan-site-v1';

    private const EXCLUDED_TABLES = [
        'cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs', 'migrations', 'sessions',
    ];

    private const SECTION_LABELS = [
        'all' => 'همه‌ی موارد اصلی سایت',
        'users' => 'کاربران و حساب‌های کاربری',
        'accounting' => 'حسابداری، سفارش‌ها و اعتبارها',
        'products' => 'محصولات و تنظیمات تولید',
        'settings' => 'تنظیمات و محتوای سایت',
        'activity' => 'گزارش‌ها، رشد و فعالیت‌ها',
        'gallery' => 'گالری خصوصی کاربران',
        'media' => 'فایل‌های رسانه‌ای فضای عمومی',
    ];

    public function create(array $sections): string
    {
        $sections = $this->normalizeSections($sections);
        $directory = $this->directory();
        File::ensureDirectoryExists($directory);

        $filename = 'site-' . now()->format('Y-m-d-His') . '.zip';
        $zipPath = $directory . DIRECTORY_SEPARATOR . $filename;
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('ساخت فایل پشتیبان سایت ممکن نشد.');
        }

        $tempDirectory = $directory . DIRECTORY_SEPARATOR . '.working-' . Str::random(16);
        File::ensureDirectoryExists($tempDirectory . DIRECTORY_SEPARATOR . 'database/tables');
        $tables = $this->tablesForSections($sections);
        $tableManifest = [];
        $totalRows = 0;
        $zipClosed = false;

        try {
            foreach ($tables as $table) {
                $metadata = $this->writeTableSnapshot($table, $tempDirectory);
                $tableManifest[] = $metadata;
                $totalRows += $metadata['rows'];
                $zip->addFile($metadata['path'], 'database/tables/' . $metadata['archive_name']);
            }

            $sqlDump = $this->createSqlDump($tables, $tempDirectory);
            if ($sqlDump) {
                $zip->addFile($sqlDump, 'database/database.sql');
            }

            $media = array_merge(
                $this->addPublicMedia($zip, $sections),
                $this->addPrivateGalleryMedia($zip, $sections),
            );
            $manifest = [
                'format' => self::FORMAT,
                'created_at' => now()->toIso8601String(),
                'filename' => $filename,
                'application' => config('app.name'),
                'database_driver' => DB::getDriverName(),
                'sections' => $sections,
                'section_labels' => collect($sections)->mapWithKeys(fn (string $section) => [$section => self::SECTION_LABELS[$section] ?? $section])->all(),
                'tables_count' => count($tableManifest),
                'rows_count' => $totalRows,
                'tables' => collect($tableManifest)->map(fn (array $table) => collect($table)->except(['path'])->all())->values()->all(),
                'sql_dump' => [
                    'included' => (bool) $sqlDump,
                    'path' => $sqlDump ? 'database/database.sql' : null,
                    'note' => $sqlDump ? 'قابل اجرای مستقیم روی پایگاه‌داده MySQL/MariaDB' : 'ابزار mysqldump در محیط اجرا پیدا نشد؛ snapshot جدول‌ها برای بازیابی برنامه‌ای موجود است.',
                ],
                'files_count' => count($media),
                'files' => $media,
            ];

            $zip->addFromString('manifest.json', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE));
            $zip->close();
            $zipClosed = true;

            return $zipPath;
        } finally {
            if (!$zipClosed && $zip instanceof ZipArchive) {
                @$zip->close();
            }
            File::deleteDirectory($tempDirectory);
        }
    }

    public function restore(UploadedFile $upload): array
    {
        $zip = new ZipArchive();
        $tempDirectory = $this->directory() . DIRECTORY_SEPARATOR . '.restore-' . Str::random(16);
        File::ensureDirectoryExists($tempDirectory);

        try {
            if ($zip->open($upload->getRealPath()) !== true || $zip->locateName('manifest.json') === false) {
                throw new RuntimeException('فایل پشتیبان سایت معتبر نیست.');
            }
            $this->validateArchiveEntries($zip);
            $manifest = json_decode($zip->getFromName('manifest.json'), true, 512, JSON_THROW_ON_ERROR);
            if (($manifest['format'] ?? '') !== self::FORMAT) {
                throw new RuntimeException('نسخه فایل پشتیبان سایت پشتیبانی نمی‌شود.');
            }
            $zip->extractTo($tempDirectory);

            $restoredRows = 0;
            $skippedTables = 0;
            Schema::disableForeignKeyConstraints();
            try {
                foreach ((array) ($manifest['tables'] ?? []) as $table) {
                    $tableName = (string) ($table['name'] ?? '');
                    $archiveName = (string) ($table['archive_name'] ?? '');
                    $snapshotPath = $tempDirectory . DIRECTORY_SEPARATOR . 'database/tables/' . $archiveName;
                    if (!$this->safeTableName($tableName) || !File::exists($snapshotPath) || !Schema::hasTable($tableName)) {
                        $skippedTables++;
                        continue;
                    }
                    $payload = json_decode(File::get($snapshotPath), true, 512, JSON_THROW_ON_ERROR);
                    $rows = (array) ($payload['rows'] ?? []);
                    $primary = (array) ($payload['primary_key'] ?? []);
                    foreach (array_chunk($rows, 250) as $chunk) {
                        if ($chunk === []) continue;
                        $query = DB::table($tableName);
                        if ($primary !== []) {
                            $columns = array_keys((array) $chunk[0]);
                            $updates = array_values(array_diff($columns, $primary));
                            $query->upsert($chunk, $primary, $updates);
                        } else {
                            $query->insertOrIgnore($chunk);
                        }
                        $restoredRows += count($chunk);
                    }
                }

                foreach ((array) ($manifest['files'] ?? []) as $file) {
                    $original = (string) ($file['original_path'] ?? '');
                    $archive = (string) ($file['archive_path'] ?? '');
                    $source = $tempDirectory . DIRECTORY_SEPARATOR . $archive;
                    if (!$this->safePath($original) || !File::exists($source)) continue;
                    $disk = (string) ($file['disk'] ?? 'public');
                    $archivePrefix = $disk === 'user_gallery' ? 'storage-private/' : 'storage-public/';
                    if (!Str::startsWith($archive, $archivePrefix)) continue;
                    Storage::disk($disk)->put($original, File::get($source));
                }
            } finally {
                Schema::enableForeignKeyConstraints();
            }

            return [
                'rows' => $restoredRows,
                'tables' => max(0, (int) ($manifest['tables_count'] ?? 0) - $skippedTables),
                'skipped_tables' => $skippedTables,
                'files' => count((array) ($manifest['files'] ?? [])),
            ];
        } finally {
            if ($zip instanceof ZipArchive) @$zip->close();
            File::deleteDirectory($tempDirectory);
        }
    }

    public function isSiteArchive(UploadedFile $upload): bool
    {
        $zip = new ZipArchive();
        try {
            return $zip->open($upload->getRealPath()) === true
                && $zip->locateName('manifest.json') !== false
                && (($manifest = json_decode((string) $zip->getFromName('manifest.json'), true))['format'] ?? '') === self::FORMAT;
        } finally {
            if ($zip instanceof ZipArchive) @$zip->close();
        }
    }

    public function directory(): string
    {
        return storage_path('app/' . self::DIRECTORY);
    }

    public function sectionLabels(): array
    {
        return self::SECTION_LABELS;
    }

    public function availableTableCount(array $sections): int
    {
        return count($this->tablesForSections($this->normalizeSections($sections)));
    }

    private function normalizeSections(array $sections): array
    {
        $sections = array_values(array_intersect(array_unique(array_map('strval', $sections)), array_keys(self::SECTION_LABELS)));
        if (in_array('all', $sections, true)) return ['all'];
        return $sections === [] ? array_keys(self::SECTION_LABELS) : $sections;
    }

    private function tablesForSections(array $sections): array
    {
        $tables = collect($this->tableListing())->reject(fn (string $table) => in_array($table, self::EXCLUDED_TABLES, true));
        if (in_array('all', $sections, true)) return $tables->values()->all();

        return $tables->filter(function (string $table) use ($sections): bool {
            foreach ($sections as $section) {
                if ($this->tableBelongsToSection($table, $section)) return true;
            }
            return false;
        })->values()->all();
    }

    private function tableBelongsToSection(string $table, string $section): bool
    {
        $patterns = match ($section) {
            'users' => ['users', 'user_*', 'auth_events', 'otps', 'password_reset_tokens', 'sessions', 'support_tickets', 'support_ticket_messages', 'saved_products', 'liked_products', 'user_uploads', 'generated_images', 'generated_videos', 'generations'],
            'accounting' => ['orders', 'order_events', 'plan_purchases', 'finance_*', 'wallets', 'service_credit_*', 'token_logs', 'discounts', 'payment_*', 'customer_accounts', 'deal_*', 'deals'],
            'products' => ['products', 'categories', 'category_product', 'ai_models', 'ai_provider_*', 'model_*', 'product_*', 'prompts', 'lab_*', 'video_*'],
            'settings' => ['*_settings', 'site_*', 'home_*', 'feed_*', 'marketing_*', 'trend_*', 'article_*', 'website_*', 'sms_*', 'telegram_*'],
            'activity' => ['activity_logs', 'crm_*', 'growth_*', 'referral_*', 'follow_ups', 'person_*', 'people', 'people_*', 'authentication_logs'],
            'gallery' => ['user_gallery_*'],
            'media' => [],
            default => [],
        };

        return collect($patterns)->contains(fn (string $pattern) => Str::is($pattern, $table));
    }

    private function tableListing(): array
    {
        $schema = Schema::getConnection()->getSchemaBuilder();
        $database = (string) config('database.connections.' . config('database.default') . '.database');
        $tables = method_exists($schema, 'getTables')
            ? collect($schema->getTables())
                ->when(in_array(DB::getDriverName(), ['mysql', 'mariadb'], true), fn ($items) => $items->filter(fn ($table) => (string) ($table['schema'] ?? $database) === $database))
                ->pluck('name')
                ->all()
            : $schema->getTableListing();
        return collect($tables)->map(fn ($table) => (string) $table)->filter()->unique()->values()->all();
    }

    private function writeTableSnapshot(string $table, string $tempDirectory): array
    {
        $columns = Schema::getColumnListing($table);
        $primary = $this->primaryKeyColumns($table, $columns);
        $archiveName = hash('sha256', $table) . '-' . $table . '.json';
        $path = $tempDirectory . DIRECTORY_SEPARATOR . 'database/tables/' . $archiveName;
        $handle = fopen($path, 'wb');
        fwrite($handle, json_encode(['table' => $table, 'columns' => $columns, 'primary_key' => $primary, 'rows' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
        fclose($handle);

        $rows = DB::table($table)->cursor();
        $payload = ['table' => $table, 'columns' => $columns, 'primary_key' => $primary, 'rows' => []];
        foreach ($rows as $row) $payload['rows'][] = (array) $row;
        File::put($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));

        return ['name' => $table, 'archive_name' => $archiveName, 'columns' => $columns, 'primary_key' => $primary, 'rows' => count($payload['rows']), 'path' => $path];
    }

    private function primaryKeyColumns(string $table, array $columns): array
    {
        $schema = Schema::getConnection()->getSchemaBuilder();
        if (method_exists($schema, 'getIndexes')) {
            $primary = collect($schema->getIndexes($table))->first(fn (array $index) => (bool) ($index['primary'] ?? false));
            if ($primary) return array_values((array) ($primary['columns'] ?? []));
        }
        return in_array('id', $columns, true) ? ['id'] : [];
    }

    private function addPublicMedia(ZipArchive $zip, array $sections): array
    {
        if (!in_array('media', $sections, true) && !in_array('all', $sections, true)) return [];
        $files = [];
        foreach (Storage::disk('public')->allFiles() as $path) {
            if (!$this->safePath($path)) continue;
            $archivePath = 'storage-public/' . hash('sha256', $path) . '-' . basename($path);
            $zip->addFromString($archivePath, Storage::disk('public')->get($path));
            $files[] = ['disk' => 'public', 'original_path' => $path, 'archive_path' => $archivePath, 'bytes' => Storage::disk('public')->size($path)];
        }
        return $files;
    }

    private function addPrivateGalleryMedia(ZipArchive $zip, array $sections): array
    {
        if (!in_array('gallery', $sections, true) && !in_array('all', $sections, true)) return [];
        $files = [];
        $disk = Storage::disk('user_gallery');
        foreach ($disk->allFiles() as $path) {
            if (!$this->safePath($path)) continue;
            $archivePath = 'storage-private/' . hash('sha256', $path) . '-' . basename($path);
            $zip->addFromString($archivePath, $disk->get($path));
            $files[] = ['disk' => 'user_gallery', 'original_path' => $path, 'archive_path' => $archivePath, 'bytes' => $disk->size($path)];
        }
        return $files;
    }

    private function createSqlDump(array $tables, string $tempDirectory): ?string
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) || $tables === []) return null;
        $binary = $this->findMysqlDump();
        if (!$binary) return null;
        $path = $tempDirectory . DIRECTORY_SEPARATOR . 'database.sql';
        $handle = fopen($path, 'wb');
        $command = [$binary, '--single-transaction', '--quick', '--skip-lock-tables', '--hex-blob', '--routines', '--triggers', '--add-drop-table', '--complete-insert', '--default-character-set=utf8mb4', '--host=' . config('database.connections.' . config('database.default') . '.host'), '--port=' . config('database.connections.' . config('database.default') . '.port'), '--user=' . config('database.connections.' . config('database.default') . '.username'), (string) config('database.connections.' . config('database.default') . '.database')];
        array_push($command, ...$tables);
        $process = new Process($command, base_path(), ['MYSQL_PWD' => (string) config('database.connections.' . config('database.default') . '.password')]);
        $process->setTimeout(900);
        $stderr = '';
        $exitCode = $process->run(function (string $type, string $buffer) use ($handle, &$stderr): void {
            if ($type === Process::OUT) fwrite($handle, $buffer);
            else $stderr .= $buffer;
        });
        fclose($handle);
        if ($exitCode !== 0 || !File::exists($path) || File::size($path) === 0) {
            File::delete($path);
            return null;
        }
        return $path;
    }

    private function findMysqlDump(): ?string
    {
        $candidates = [
            trim((string) shell_exec('command -v mysqldump 2>/dev/null')),
            '/Applications/MAMP/Library/bin/mysql80/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];
        foreach ($candidates as $candidate) if ($candidate !== '' && is_executable($candidate)) return $candidate;
        return null;
    }

    private function validateArchiveEntries(ZipArchive $zip): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);
            if ($name === 'manifest.json' || $name === 'database/database.sql') continue;
            if (!Str::startsWith($name, ['database/tables/', 'storage-public/', 'storage-private/']) || !$this->safePath($name)) {
                throw new RuntimeException('ساختار فایل پشتیبان سایت معتبر نیست.');
            }
        }
    }

    private function safeTableName(string $table): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]+$/', $table);
    }

    private function safePath(string $path): bool
    {
        return $path !== '' && !Str::startsWith($path, ['/', '\\']) && !str_contains($path, '..') && !str_contains($path, '\\');
    }
}
