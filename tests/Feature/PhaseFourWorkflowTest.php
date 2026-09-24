<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PhaseFourWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_filtered_excel_and_pdf_exports_are_downloadable(): void
    {
        Expense::factory()->create(['period_month' => '2026-09-01', 'description' => 'Included', 'amount' => '120.00']);
        Expense::factory()->create(['period_month' => '2026-08-01', 'description' => 'Excluded', 'amount' => '999.00']);
        $user = User::factory()->staff()->create();

        $excel = $this->actingAs($user)->get('/api/exports/monthly.xlsx?year=2026&month=9')->assertOk();
        $this->assertStringContainsString('spreadsheetml', (string) $excel->headers->get('content-type'));
        $path = tempnam(sys_get_temp_dir(), 'export').'.xlsx';
        file_put_contents($path, $excel->streamedContent());
        $workbook = IOFactory::load($path);
        $this->assertSame(['Summary', 'Expenses', 'Category Summary'], $workbook->getSheetNames());
        $this->assertSame('Included', $workbook->getSheetByName('Expenses')->getCell('C2')->getValue());
        @unlink($path);

        $this->actingAs($user)->get('/api/exports/monthly.pdf?year=2026&month=9')
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_can_preview_and_commit_a_workbook_with_reconciliation(): void
    {
        Storage::fake('local');
        $path = tempnam(sys_get_temp_dir(), 'import').'.xlsx';
        $workbook = new Spreadsheet;
        $sheet = $workbook->getActiveSheet();
        $sheet->setTitle('September-2026');
        $sheet->fromArray([
            ['Office cost list'], ['Date', 'Cost type', 'Cost', 'Paid by'],
            ['2026-09-01', 'Internet', 1200, 'Mehorab'],
            ['2026-09-02', 'Stationery', '=500+250', 'Mehorab'],
            ['2026-09-03', null, null, 'Mehorab'],
        ]);
        (new Xlsx($workbook))->save($path);
        $admin = User::factory()->admin()->create();

        $analysis = $this->actingAs($admin)->post('/api/imports/analyze', [
            'file' => new UploadedFile($path, 'office-costs.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ])->assertCreated()->json('data');
        $preview = $this->postJson("/api/imports/{$analysis['batch']['id']}/preview", [
            'sheets' => [['name' => 'September-2026', 'header_row' => 2,
                'mapping' => ['expense_date' => 'A', 'description' => 'B', 'amount' => 'C', 'payer' => 'D']]],
        ])->assertOk()->assertJsonPath('data.valid_count', 2)->assertJsonPath('data.invalid_count', 1);
        $this->assertSame('1950.00', $preview->json('data.valid_total'));

        $this->postJson("/api/imports/{$analysis['batch']['id']}/commit")
            ->assertOk()->assertJsonPath('data.imported_count', 2)
            ->assertJsonPath('data.verification.database_total', '1950.00')
            ->assertJsonPath('data.verification.difference', '0.00')
            ->assertJsonPath('data.verification.payment_totals.unspecified', 1950);
        $this->assertDatabaseCount('expenses', 2);
        $this->assertDatabaseHas('expense_payer_allocations', ['payer_name' => 'Mehorab', 'amount' => 1200]);
        @unlink($path);
    }

    public function test_staff_cannot_import_workbooks(): void
    {
        $this->actingAs(User::factory()->staff()->create())->postJson('/api/imports/analyze')->assertForbidden();
    }
}
