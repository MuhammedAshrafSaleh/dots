<?php

namespace App\Livewire\Admin\Products;

use App\Jobs\ApplyProductImport;
use App\Models\ImportLog;
use App\Services\ProductExportBuilder;
use App\Services\ProductImportParser;
use App\Services\ProductImportReportBuilder;
use App\Services\ProductTemplateBuilder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Import extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $xlsxFile = null;

    public ?TemporaryUploadedFile $zipFile = null;

    public bool $showPreview = false;

    /** @var array{create:int, update:int, variants:int, specs:int, images:int} */
    public array $counts = ['create' => 0, 'update' => 0, 'variants' => 0, 'specs' => 0, 'images' => 0];

    /** @var list<array{sheet:string, row:int, column:string, message:string}> */
    public array $parseErrors = [];

    /** @var list<array{sheet:string, row:int, column:string, message:string}> */
    public array $parseWarnings = [];

    public bool $hasValidRows = false;

    // Apply state
    public ?int $importLogId = null;

    public bool $applying = false;

    public bool $applied = false;

    public bool $applyFailed = false;

    public bool $applyStalled = false;

    /** Unix timestamp set when the job is dispatched — used to detect a stalled queue worker. */
    public ?int $applyDispatchedAt = null;

    /** @var array{created:int, updated:int, variants:int, specs:int, images:int, warnings:int} */
    public array $applyResult = ['created' => 0, 'updated' => 0, 'variants' => 0, 'specs' => 0, 'images' => 0, 'warnings' => 0];

    // -------------------------------------------------------------------------

    public function downloadTemplate(): StreamedResponse
    {
        $this->authorize('products.import');

        $spreadsheet = (new ProductTemplateBuilder)->build();
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, 'DOTS-products-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function export(): StreamedResponse
    {
        $this->authorize('products.export');

        $spreadsheet = (new ProductExportBuilder)->build();
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, 'DOTS-products-export-'.now()->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function preview(): void
    {
        $this->authorize('products.import');

        $this->validate([
            'xlsxFile' => ['required', 'file', 'mimes:xlsx', 'max:20480'],
        ]);

        $result = (new ProductImportParser)->parse($this->xlsxFile->getRealPath());

        $this->counts = $result['counts'];
        $this->parseErrors = $result['errors'];
        $this->parseWarnings = $result['warnings'];
        $this->hasValidRows = $result['hasValidRows'];
        $this->showPreview = true;
    }

    public function downloadReport(): StreamedResponse
    {
        $this->authorize('products.import');

        $spreadsheet = (new ProductImportReportBuilder)->build($this->parseErrors, $this->parseWarnings);
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, 'DOTS-import-report-'.now()->format('Y-m-d-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Persist the uploaded files to a private working dir and dispatch the
     * queued Apply job. With QUEUE_CONNECTION=sync it runs inline, so the
     * status is refreshed immediately; in production the view polls.
     */
    public function apply(): void
    {
        $this->authorize('products.import');

        $this->validate([
            'xlsxFile' => ['required', 'file', 'mimes:xlsx', 'max:65536'],
            'zipFile' => ['nullable', 'file', 'mimes:zip', 'max:65536'],
        ]);

        $dir = 'imports/'.Str::uuid();
        $xlsxStored = $this->xlsxFile->storeAs($dir, 'data.xlsx', 'local');
        $zipStored = $this->zipFile?->storeAs($dir, 'media.zip', 'local');

        $log = ImportLog::create([
            'user_id' => auth('admin')->id(),
            'filename' => $this->xlsxFile->getClientOriginalName(),
            'status' => 'pending',
        ]);

        $this->importLogId = $log->id;
        $this->applying = true;
        $this->applied = false;
        $this->applyFailed = false;
        $this->applyStalled = false;
        $this->applyDispatchedAt = time();

        ApplyProductImport::dispatch(
            $log->id,
            Storage::disk('local')->path($xlsxStored),
            $zipStored !== null ? Storage::disk('local')->path($zipStored) : null,
            app()->getLocale(),
        );

        $this->refreshStatus();
    }

    /** Poll the import log; reflect the final counts once the job finishes. */
    public function refreshStatus(): void
    {
        if ($this->importLogId === null) {
            return;
        }

        $log = ImportLog::find($this->importLogId);

        if ($log === null || ! in_array($log->status, ['completed', 'failed'], true)) {
            if (
                $this->applyDispatchedAt !== null
                && (time() - $this->applyDispatchedAt) > 30
                && ($log === null || $log->status === 'pending')
            ) {
                $this->applyStalled = true;
            }

            return;
        }

        $this->applying = false;
        $this->applied = true;
        $this->applyStalled = false;
        $this->applyFailed = $log->status === 'failed';
        $this->applyResult = [
            'created' => $log->created_count,
            'updated' => $log->updated_count,
            'variants' => $log->variants_count,
            'specs' => $log->specs_count,
            'images' => $log->images_count,
            'warnings' => $log->warnings_count,
        ];
    }

    public function downloadAppliedReport(): ?StreamedResponse
    {
        $this->authorize('products.import');

        $log = $this->importLogId !== null ? ImportLog::find($this->importLogId) : null;
        if ($log === null || $log->report_path === null || ! Storage::disk('local')->exists($log->report_path)) {
            return null;
        }

        return Storage::disk('local')->download($log->report_path, 'DOTS-import-report-'.$log->id.'.xlsx');
    }

    public function cancel(): void
    {
        $this->reset([
            'xlsxFile', 'zipFile', 'showPreview', 'parseErrors', 'parseWarnings', 'hasValidRows',
            'importLogId', 'applying', 'applied', 'applyFailed', 'applyStalled', 'applyDispatchedAt',
        ]);
        $this->counts = ['create' => 0, 'update' => 0, 'variants' => 0, 'specs' => 0, 'images' => 0];
        $this->applyResult = ['created' => 0, 'updated' => 0, 'variants' => 0, 'specs' => 0, 'images' => 0, 'warnings' => 0];
    }

    public function render(): View
    {
        return view('livewire.admin.products.import')
            ->layout('components.layouts.admin', ['title' => __('admin.import_products.title')]);
    }
}
