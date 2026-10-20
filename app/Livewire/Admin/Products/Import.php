<?php

namespace App\Livewire\Admin\Products;

use App\Services\ProductExportBuilder;
use App\Services\ProductTemplateBuilder;
use Illuminate\View\View;
use Livewire\Component;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Import extends Component
{
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

    public function render(): View
    {
        return view('livewire.admin.products.import')
            ->layout('components.layouts.admin', ['title' => __('admin.import_products.title')]);
    }
}
