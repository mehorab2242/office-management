<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SpreadsheetExportService
{
    public function expenses(Builder $query, string $title = 'Expenses'): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $this->writeExpenses($spreadsheet->getActiveSheet(), $query, $title);

        return $spreadsheet;
    }

    public function report(Builder $query, array $summary, array $categories, string $title, ?array $months = null): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Summary');
        $summarySheet->fromArray([
            ['Metric', 'Value'],
            ['Total amount (BDT)', (float) $summary['total_amount']],
            ['Transactions', $summary['transaction_count']],
            ['Average (BDT)', (float) ($summary['average_amount'] ?? 0)],
        ]);
        $this->formatTable($summarySheet, 2, 8);
        $summarySheet->getStyle('B2:B8')->getNumberFormat()->setFormatCode('#,##0.00');
        $summarySheet->setCellValue('D1', $title);
        $summarySheet->getStyle('D1')->getFont()->setBold(true)->setSize(14);

        $this->writeExpenses($spreadsheet->createSheet(), $query, 'Expenses');
        $categorySheet = $spreadsheet->createSheet();
        $categorySheet->setTitle('Category Summary');
        $categorySheet->fromArray([['Category', 'Total (BDT)']]);
        $row = 2;
        foreach ($categories as $category) {
            $categorySheet->setCellValueExplicit("A{$row}", $category['name'], DataType::TYPE_STRING);
            $categorySheet->setCellValue("B{$row}", (float) $category['total']);
            $row++;
        }
        $this->formatTable($categorySheet, 2, max(1, $row - 1));
        $categorySheet->getStyle("B2:B{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

        if ($months !== null) {
            $monthSheet = $spreadsheet->createSheet();
            $monthSheet->setTitle('Monthly Summary');
            $monthSheet->fromArray([['Month', 'Total (BDT)', 'Transactions']]);
            $row = 2;
            foreach ($months as $month) {
                $monthSheet->fromArray([
                    date('F', mktime(0, 0, 0, $month['month'], 1)),
                    (float) $month['total'],
                    $month['transaction_count'],
                ], null, "A{$row}");
                $row++;
            }
            $this->formatTable($monthSheet, 3, max(1, $row - 1));
            $monthSheet->getStyle("B2:B{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    public function output(Spreadsheet $spreadsheet): void
    {
        (new Xlsx($spreadsheet))->save('php://output');
        $spreadsheet->disconnectWorksheets();
    }

    private function writeExpenses(Worksheet $sheet, Builder $query, string $title): void
    {
        $sheet->setTitle(substr($title, 0, 31));
        $headers = ['Date', 'Period', 'Description', 'Category', 'Amount (BDT)', 'Method', 'Paid by', 'Reference', 'Note', 'Created by'];
        $sheet->fromArray($headers);
        $row = 2;
        foreach ((clone $query)->with(['category', 'payerAllocations', 'creator'])->lazy(500) as $expense) {
            $sheet->fromArray([
                $expense->expense_date ? ExcelDate::PHPToExcel($expense->expense_date) : null,
                $expense->expense_date ? $expense->expense_date->format('Y-m') : null,
                $expense->description,
                $expense->category?->name ?? 'Uncategorized',
                (float) $expense->amount,
                $expense->payment_method,
                $expense->payerAllocations->pluck('payer_name')->implode(', '),
                $expense->reference,
                $expense->note,
                $expense->creator?->name,
            ], null, "A{$row}");
            foreach (['C', 'D', 'F', 'G', 'H', 'I', 'J'] as $column) {
                $sheet->setCellValueExplicit("{$column}{$row}", (string) ($sheet->getCell("{$column}{$row}")->getValue() ?? ''), DataType::TYPE_STRING);
            }
            $row++;
        }
        $this->formatTable($sheet, count($headers), max(1, $row - 1));
        $sheet->getStyle("A2:A{$row}")->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        $sheet->getStyle("B2:B{$row}")->getNumberFormat()->setFormatCode('yyyy-mm');
        $sheet->getStyle("E2:E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
    }

    private function formatTable(Worksheet $sheet, int $columns, int $lastRow): void
    {
        $lastColumn = Coordinate::stringFromColumnIndex($columns);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F766E');
        for ($column = 1; $column <= $columns; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }
    }
}
