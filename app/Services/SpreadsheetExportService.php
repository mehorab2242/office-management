<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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
            ['Paid (BDT)', (float) $summary['paid_amount']],
            ['Unpaid (BDT)', (float) $summary['unpaid_amount']],
            ['Pending (BDT)', (float) $summary['pending_amount']],
            ['Unspecified (BDT)', (float) $summary['unspecified_amount']],
            ['Transactions', $summary['transaction_count']],
            ['Average (BDT)', (float) ($summary['average_amount'] ?? 0)],
        ]);
        $this->formatTable($summarySheet, 2, 8);
        $summarySheet->setCellValue('D1', $title);
        $summarySheet->getStyle('D1')->getFont()->setBold(true)->setSize(14);

        $this->writeExpenses($spreadsheet->createSheet(), $query, 'Expenses');
        $categorySheet = $spreadsheet->createSheet();
        $categorySheet->setTitle('Category Summary');
        $categorySheet->fromArray([['Category', 'Total (BDT)']]);
        $row = 2;
        foreach ($categories as $category) {
            $categorySheet->fromArray([$category['name'], (float) $category['total']], null, "A{$row}");
            $row++;
        }
        $this->formatTable($categorySheet, 2, max(1, $row - 1));

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
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    public function output(Spreadsheet $spreadsheet): void
    {
        (new Xlsx($spreadsheet))->save('php://output');
        $spreadsheet->disconnectWorksheets();
    }

    private function writeExpenses(object $sheet, Builder $query, string $title): void
    {
        $sheet->setTitle(substr($title, 0, 31));
        $headers = ['Date', 'Period', 'Description', 'Category', 'Amount (BDT)', 'Status', 'Method', 'Paid by', 'Reference', 'Note', 'Created by'];
        $sheet->fromArray($headers);
        $row = 2;
        foreach ((clone $query)->with(['category', 'payerAllocations'])->cursor() as $expense) {
            $sheet->fromArray([
                $expense->expense_date?->format('Y-m-d'),
                $expense->period_month?->format('Y-m'),
                $expense->description,
                $expense->category?->name ?? 'Uncategorized',
                (float) $expense->amount,
                $expense->payment_status ?? 'Unspecified',
                $expense->payment_method,
                $expense->payerAllocations->pluck('payer_name')->implode(', '),
                $expense->reference,
                $expense->note,
                $expense->creator?->name,
            ], null, "A{$row}");
            $row++;
        }
        $this->formatTable($sheet, count($headers), max(1, $row - 1));
        $sheet->getStyle("E2:E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
    }

    private function formatTable(object $sheet, int $columns, int $lastRow): void
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
