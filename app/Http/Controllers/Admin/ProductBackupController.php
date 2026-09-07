<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductBackupService;
use App\Services\SiteBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ProductBackupController extends Controller
{
    public function index(ProductBackupService $productBackups, SiteBackupService $siteBackups)
    {
        $files = collect([
            ...$this->zipFiles($productBackups->directory()),
            ...$this->zipFiles($siteBackups->directory()),
        ])
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(fn ($file) => $this->fileSummary($file))
            ->values();
        $latest = $files->first();
        $latestProduct = $files->first(fn (array $file) => $file['has_products']);
        $productCount = Product::withTrashed()->count();
        $backedUpProductCount = $this->backedUpProductCount($latestProduct, $productCount);
        $stats = [
            'backups_count' => $files->count(),
            'last_backup' => $latest['date'] ?? 'هنوز ساخته نشده',
            'products_not_backed_up' => max(0, $productCount - $backedUpProductCount),
            'storage_size' => $this->humanSize((int) $files->sum(fn (array $file) => $file['bytes'])),
            'product_count' => $productCount,
            'backed_up_product_count' => $backedUpProductCount,
        ];

        return view('admin.settings.backup', [
            'files' => $files,
            'stats' => $stats,
            'sections' => $siteBackups->sectionLabels(),
        ]);
    }

    public function create(Request $request, ProductBackupService $productBackups, SiteBackupService $siteBackups): RedirectResponse|BinaryFileResponse
    {
        $data = $request->validate([
            'product_ids' => ['nullable', 'array', 'max:1000'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'sections' => ['nullable', 'array', 'max:20'],
            'sections.*' => ['string', 'max:40'],
            'backup_scope' => ['nullable', 'in:site'],
            'delivery' => ['nullable', 'in:server,download'],
        ]);
        $productIds = array_values(array_unique(array_map('intval', (array) ($data['product_ids'] ?? []))));
        $isSiteBackup = $request->input('backup_scope') === 'site' || $request->has('sections');
        $path = $isSiteBackup
            ? $siteBackups->create((array) ($data['sections'] ?? ['all']))
            : $productBackups->create($productIds ?: null);

        if (($data['delivery'] ?? 'server') === 'download') {
            return response()->download($path)->deleteFileAfterSend(true);
        }

        $message = $isSiteBackup
            ? 'پشتیبان انتخاب‌شده‌ی سایت با موفقیت روی سرور ذخیره شد: ' . basename($path)
            : (($productIds ? number_format(count($productIds)) . ' محصول انتخاب‌شده و ' : '') . 'بک‌آپ محصولات با موفقیت روی سرور ذخیره شد: ' . basename($path));
        return redirect()->route('admin.settings.backup')->with('success', $message);
    }

    public function download(string $backup, ProductBackupService $productBackups, SiteBackupService $siteBackups): BinaryFileResponse
    {
        abort_unless($this->isSafeBackupName($backup), 404);
        $path = $this->locateBackup($backup, $productBackups, $siteBackups);
        abort_unless($path && File::exists($path), 404);
        return response()->download($path);
    }

    public function destroy(string $backup, ProductBackupService $productBackups, SiteBackupService $siteBackups): RedirectResponse
    {
        abort_unless($this->isSafeBackupName($backup), 404);
        $path = $this->locateBackup($backup, $productBackups, $siteBackups);
        abort_unless($path && File::exists($path), 404);
        File::delete($path);
        return redirect()->route('admin.settings.backup')->with('success', 'فایل پشتیبان از روی سرور حذف شد: ' . $backup);
    }

    public function restore(Request $request, ProductBackupService $productBackups, SiteBackupService $siteBackups): RedirectResponse
    {
        $data = $request->validate(['backup' => ['required', 'file', 'mimes:zip', 'max:524288']]);
        try {
            if ($siteBackups->isSiteArchive($data['backup'])) {
                $result = $siteBackups->restore($data['backup']);
                return back()->with('success', number_format($result['rows']) . ' ردیف از ' . number_format($result['tables']) . ' جدول و ' . number_format($result['files']) . ' فایل از پشتیبان سایت بازیابی شد.');
            }
            $result = $productBackups->restore($data['backup']);
            return back()->with('success', number_format($result['products']) . ' محصول و ' . number_format($result['files']) . ' فایل از بک‌آپ بازیابی شد.');
        } catch (\Throwable $exception) {
            report($exception);
            return back()->with('error', 'ری‌استور انجام نشد؛ فایل پشتیبان معتبر یا قابل خواندن نیست.');
        }
    }

    private function isSafeBackupName(string $backup): bool
    {
        return basename($backup) === $backup && (bool) preg_match('/^(products|site)-[0-9-]+\.zip$/', $backup);
    }

    private function locateBackup(string $backup, ProductBackupService $productBackups, SiteBackupService $siteBackups): ?string
    {
        return collect([$productBackups->directory(), $siteBackups->directory()])
            ->map(fn (string $directory) => $directory . DIRECTORY_SEPARATOR . $backup)
            ->first(fn (string $candidate) => File::exists($candidate));
    }

    private function zipFiles(string $directory): array
    {
        return File::exists($directory) ? array_values(array_filter(File::files($directory), fn ($file) => $file->getExtension() === 'zip')) : [];
    }

    private function fileSummary($file): array
    {
        $manifest = [];
        $zip = new ZipArchive();
        if ($zip->open($file->getPathname()) === true && $zip->locateName('manifest.json') !== false) {
            $manifest = json_decode((string) $zip->getFromName('manifest.json'), true) ?: [];
            $zip->close();
        }
        $selected = $manifest['selected_product_ids'] ?? null;
        $siteProducts = collect((array) ($manifest['tables'] ?? []))->first(fn (array $table) => ($table['name'] ?? '') === 'products');
        return [
            'name' => $file->getFilename(),
            'size' => $this->humanSize($file->getSize()),
            'bytes' => $file->getSize(),
            'date' => date('Y/m/d H:i', $file->getMTime()),
            'format' => ($manifest['format'] ?? '') === 'vatan-site-v1' ? 'پشتیبان سایت' : 'پشتیبان محصولات',
            'sections' => array_values((array) ($manifest['section_labels'] ?? [])),
            'has_products' => $selected !== null || $siteProducts !== null || (($manifest['products_count'] ?? 0) > 0),
            'products_count' => $selected !== null ? count((array) $selected) : (int) ($siteProducts['rows'] ?? $manifest['products_count'] ?? 0),
            'selected_product_ids' => $selected,
        ];
    }

    private function backedUpProductCount(?array $file, int $productCount): int
    {
        if (!$file || !$file['has_products']) return 0;
        return $file['selected_product_ids'] === null ? $productCount : (int) $file['products_count'];
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' بایت';
        if ($bytes < 1024 ** 2) return number_format($bytes / 1024, 1) . ' کیلوبایت';
        if ($bytes < 1024 ** 3) return number_format($bytes / (1024 ** 2), 1) . ' مگابایت';
        return number_format($bytes / (1024 ** 3), 1) . ' گیگابایت';
    }
}
