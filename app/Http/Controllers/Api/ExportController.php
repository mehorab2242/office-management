<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseQuery;
use App\Services\PdfReportService;
use App\Services\ReportService;
use App\Services\SpreadsheetExportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function expenses(IndexExpenseRequest $request, ExpenseQuery $expenses, SpreadsheetExportService $exports): StreamedResponse
    {
        $query = $expenses->build($request->safe()->except(['page', 'per_page']));

        return $this->xlsx($exports, $exports->expenses($query), 'expenses.xlsx');
    }

    public function monthly(Request $request, ReportService $reports, SpreadsheetExportService $exports): StreamedResponse
    {
        [$query, $label] = $this->periodQuery($request);

        return $this->xlsx($exports, $exports->report($query, $reports->summary($query), $reports->categories($query), $label), 'monthly-report.xlsx');
    }

    public function yearly(Request $request, ReportService $reports, SpreadsheetExportService $exports): StreamedResponse
    {
        Gate::authorize('viewAny', Expense::class);
        $data = $request->validate(['year' => ['required', 'integer', 'between:2000,2100']]);
        $query = Expense::query()->whereYear('period_month', $data['year']);

        return $this->xlsx($exports, $exports->report($query, $reports->summary($query), $reports->categories($query), "Year {$data['year']}", $reports->months((int) $data['year'])), 'yearly-report.xlsx');
    }

    public function custom(IndexExpenseRequest $request, ExpenseQuery $expenses, ReportService $reports, SpreadsheetExportService $exports): StreamedResponse
    {
        $query = $expenses->build($request->safe()->except(['page', 'per_page']));

        return $this->xlsx($exports, $exports->report($query, $reports->summary($query), $reports->categories($query), 'Custom report'), 'custom-report.xlsx');
    }

    public function monthlyPdf(Request $request, ReportService $reports, PdfReportService $pdf): Response
    {
        [$query, $label] = $this->periodQuery($request);

        return $this->pdf($pdf, $query, $reports, $label, 'monthly-report.pdf');
    }

    public function customPdf(IndexExpenseRequest $request, ExpenseQuery $expenses, ReportService $reports, PdfReportService $pdf): Response
    {
        return $this->pdf($pdf, $expenses->build($request->safe()->except(['page', 'per_page'])), $reports, 'Custom expense report', 'custom-report.pdf');
    }

    /** @return array{0: Builder<Expense>, 1: string} */
    private function periodQuery(Request $request): array
    {
        Gate::authorize('viewAny', Expense::class);
        $data = $request->validate(['year' => ['required', 'integer', 'between:2000,2100'], 'month' => ['required', 'integer', 'between:1,12']]);
        $query = Expense::query()->whereYear('period_month', $data['year'])->whereMonth('period_month', $data['month']);

        return [$query, date('F Y', mktime(0, 0, 0, (int) $data['month'], 1, (int) $data['year']))];
    }

    private function xlsx(SpreadsheetExportService $exports, Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(fn () => $exports->output($spreadsheet), $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function pdf(PdfReportService $pdf, Builder $query, ReportService $reports, string $title, string $filename): Response
    {
        $content = $pdf->render([
            'title' => $title,
            'summary' => $reports->summary($query),
            'categories' => $reports->categories($query),
            'expenses' => (clone $query)->with(['category', 'payerAllocations'])->get(),
        ]);

        return response($content, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => "attachment; filename=\"{$filename}\""]);
    }
}
