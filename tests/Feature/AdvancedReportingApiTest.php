<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedReportingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_yearly_report_aggregates_months_and_categories_in_the_database(): void
    {
        $utilities = Category::factory()->create(['name' => 'Utilities']);
        Expense::factory()->create(['period_month' => '2026-01-01', 'expense_date' => '2026-01-10', 'amount' => '100.00', 'payment_status' => 'paid', 'category_id' => $utilities->id]);
        Expense::factory()->create(['period_month' => '2026-02-01', 'expense_date' => '2026-02-10', 'amount' => '200.00', 'payment_status' => 'unpaid', 'category_id' => $utilities->id]);
        Expense::factory()->create(['period_month' => '2025-12-01', 'expense_date' => '2025-12-10', 'amount' => '900.00']);

        $this->actingAs(User::factory()->staff()->create())
            ->getJson('/api/reports/yearly?year=2026')
            ->assertOk()
            ->assertJsonPath('data.summary.total_amount', '300.00')
            ->assertJsonPath('data.summary.transaction_count', 2)
            ->assertJsonPath('data.months.0.month', 1)
            ->assertJsonPath('data.months.0.total', '100.00')
            ->assertJsonPath('data.categories.0.name', 'Utilities')
            ->assertJsonPath('data.categories.0.total', '300.00');
    }

    public function test_custom_report_uses_expense_filters_for_summary_and_rows(): void
    {
        $included = Expense::factory()->create(['description' => 'Internet service', 'amount' => '150.00', 'payment_status' => 'paid']);
        Expense::factory()->create(['description' => 'Office lunch', 'amount' => '500.00', 'payment_status' => 'unpaid']);

        $this->actingAs(User::factory()->staff()->create())
            ->getJson('/api/reports/custom?search=Internet&payment_status=paid')
            ->assertOk()
            ->assertJsonPath('data.summary.total_amount', '150.00')
            ->assertJsonPath('data.summary.transaction_count', 1)
            ->assertJsonPath('data.expenses.0.id', $included->id)
            ->assertJsonCount(1, 'data.expenses');
    }

    public function test_report_parameters_are_validated(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->getJson('/api/reports/yearly?year=1900')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('year');
    }
}
