<?php

namespace App\Livewire\Admin\Products;

use App\Services\ProductExportBuilder;
use App\Services\ProductImportParser;
use App\Services\ProductImportReportBuilder;
use App\Services\ProductTemplateBuilder;
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

    public bool $showPreview = false;

    /** @var array{create:int, update:int, variants:int, specs:int, images:int} */
    public array $counts = ['create' => 0, 'update' => 0, 'variants' => 0, 'specs' => 0, 'images' => 0];

    /** @var list<array{sheet:string, row:int, column:string, message:string}> */
    public array $parseErrors = [];

    /** @var list<array{sheet:string, row:int, column:string, message:string}> */
    public array $parseWarnings = [];

    public bool $hasValidRows = false;

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

    public function cancel(): void
    {
        $this->xlsxFile = null;
        $this->showPreview = false;
        $this->counts = ['create' => 0, 'update' => 0, 'variants' => 0, 'specs' => 0, 'images' => 0];
        $this->parseErrors = [];
        $this->parseWarnings = [];
        $this->hasValidRows = false;
    }

    public function render(): View
    {
        return view('livewire.admin.products.import')
            ->layout('components.layouts.admin', ['title' => __('admin.import_products.title')]);
    }
}
