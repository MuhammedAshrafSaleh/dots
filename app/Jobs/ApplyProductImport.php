<?php

namespace App\Jobs;

use App\Exceptions\MediaImportException;
use App\Models\ImportLog;
use App\Services\ProductImportApplier;
use App\Services\ProductImportParser;
use App\Services\ProductImportReportBuilder;
use App\Services\ProductMediaExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

/**
 * Applies a previewed import: parses the stored xlsx, extracts the optional
 * media ZIP, writes all valid rows in one transaction, attaches media, flushes
 * caches, records the ImportLog, and cleans up the working files.
 */
class ApplyProductImport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public int $logId,
        public string $xlsxPath,
        public ?string $zipPath,
        public string $locale,
    ) {
        $this->onQueue('imports');
    }

    public function handle(
        ProductImportParser $parser,
        ProductMediaExtractor $extractor,
        ProductImportApplier $applier,
        ProductImportReportBuilder $reportBuilder,
    ): void {
        app()->setLocale($this->locale);

        $log = ImportLog::find($this->logId);
        if ($log === null) {
            $this->cleanupWorkingFiles(null);

            return;
        }

        $log->update(['status' => 'processing']);

        $mediaDir = null;

        try {
            $parsed = $parser->parse($this->xlsxPath);
            $warnings = $parsed['warnings'];

            $mediaFiles = null;
            if ($this->zipPath !== null && is_file($this->zipPath)) {
                try {
                    $extracted = $extractor->extract($this->zipPath, $warnings);
                    $mediaFiles = $extracted['files'];
                    $mediaDir = $extracted['dir'];
                } catch (MediaImportException $e) {
                    $warnings[] = [
                        'sheet' => __('admin.import_products.media_sheet'),
                        'row' => 0,
                        'column' => 'zip',
                        'message' => $e->getMessage(),
                    ];
                }
            }

            $counts = $applier->apply($parsed['plan'], $mediaFiles, $warnings);

            $reportPath = $this->saveReport($reportBuilder, $log->id, $parsed['errors'], $warnings);

            $log->update([
                'status' => 'completed',
                'created_count' => $counts['created'],
                'updated_count' => $counts['updated'],
                'variants_count' => $counts['variants'],
                'specs_count' => $counts['specs'],
                'images_count' => $counts['images'],
                'errors_count' => count($parsed['errors']),
                'warnings_count' => count($warnings),
                'report_path' => $reportPath,
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            $log->update(['status' => 'failed', 'finished_at' => now()]);
            report($e);
        } finally {
            $this->cleanupMediaDir($mediaDir);
            $this->cleanupWorkingFiles($this->xlsxPath);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $errors
     * @param  list<array<string, mixed>>  $warnings
     */
    private function saveReport(ProductImportReportBuilder $builder, int $logId, array $errors, array $warnings): string
    {
        $spreadsheet = $builder->build($errors, $warnings);
        $relative = 'imports/reports/import-'.$logId.'.xlsx';
        Storage::disk('local')->makeDirectory('imports/reports');
        (new Xlsx($spreadsheet))->save(Storage::disk('local')->path($relative));

        return $relative;
    }

    private function cleanupMediaDir(?string $dir): void
    {
        if ($dir === null || ! is_dir($dir)) {
            return;
        }
        foreach ((array) glob($dir.'/*') as $file) {
            @unlink((string) $file);
        }
        @rmdir($dir);
    }

    private function cleanupWorkingFiles(?string $xlsxPath): void
    {
        if ($this->zipPath !== null && is_file($this->zipPath)) {
            @unlink($this->zipPath);
        }
        if ($xlsxPath !== null && is_file($xlsxPath)) {
            @unlink($xlsxPath);
            @rmdir(dirname($xlsxPath));
        }
    }
}
