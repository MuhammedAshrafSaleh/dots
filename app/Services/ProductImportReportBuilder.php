<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Builds a downloadable .xlsx report of import parse errors and warnings.
 *
 * @param  list<array{sheet: string, row: int, column: string, message: string}>  $errors
 * @param  list<array{sheet: string, row: int, column: string, message: string}>  $warnings
 */
final class ProductImportReportBuilder
{
    /** @param list<array<string,mixed>> $errors
     *  @param list<array<string,mixed>> $warnings */
    public function build(array $errors, array $warnings): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        $this->addSheet($spreadsheet, 'الأخطاء', $errors, 'FFFFE0E0');
        $this->addSheet($spreadsheet, 'التحذيرات', $warnings, 'FFFFF3CD');

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /** @param list<array<string,mixed>> $rows */
    private function addSheet(Spreadsheet $spreadsheet, string $title, array $rows, string $headerBg): void
    {
        $sheet = new Worksheet($spreadsheet, $title);
        $spreadsheet->addSheet($sheet);

        $headers = ['الورقة', 'رقم الصف', 'العمود', 'الرسالة'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col.'1', $h);
            $sheet->getStyle($col.'1')
                ->getFont()->setBold(true);
            $sheet->getStyle($col.'1')
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($headerBg);
            $sheet->getStyle($col.'1')
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        foreach ($rows as $i => $item) {
            $excelRow = $i + 2;
            $sheet->setCellValue('A'.$excelRow, $item['sheet'] ?? '');
            $sheet->setCellValue('B'.$excelRow, $item['row'] ?? '');
            $sheet->setCellValue('C'.$excelRow, $item['column'] ?? '');
            $sheet->setCellValue('D'.$excelRow, $item['message'] ?? '');
        }

        foreach (['A', 'B', 'C', 'D'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getStyle('D2:D'.(max(count($rows), 1) + 1))
            ->getAlignment()->setWrapText(true);
    }
}
