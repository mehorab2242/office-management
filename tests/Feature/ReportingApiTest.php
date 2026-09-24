<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_report_includes_undated_expense_and_category_totals(): void
    {
        $category = Category::factory()->create(['name' => 'Utilities']);
        Expense::factory()->create([
            'category_id' => $category->id, 'period_month' => '2026-09-01',
            'expense_date' => '2026-09-01', 'amount' => '300.00', 'payment_status' => 'paid',
        ]);
        Expense::factory()->create([
            'category_id' => $category->id, 'period_month' => '2026-09-01',
            'expense_date' => null, 'amount' => '200.00', 'payment_status' => null,
        ]);
        Expense::factory()->create([
            'period_month' => '2026-08-01', 'expense_date' => '2026-08-01',
            'amount' => '900.00', 'payment_status' => 'unpaid',
        ]);

        $this->actingAs(User::factory()->staff()->create())
            ->getJson('/api/reports/monthly?year=2026&month=9')
            ->assertOk()->assertJsonPath('data.total_amount', '500.00')
            ->assertJsonPath('data.paid_amount', '300.00')
            ->assertJsonPath('data.unspecified_amount', '200.00')
            ->assertJsonPath('data.transaction_count', 2)
            ->assertJsonPath('data.categories.0.name', 'Utilities')
            ->assertJsonPath('data.categories.0.total', '500.00');
    }

    public function test_dashboard_totals_are_derived_from_expenses(): void
    {
        Expense::factory()->create(['amount' => '150.00', 'payment_status' => 'paid']);
        Expense::factory()->create(['amount' => '50.00', 'payment_status' => 'unpaid']);

        $this->actingAs(User::factory()->staff()->create())->getJson('/api/dashboard')
            ->assertOk()->assertJsonPath('data.total_amount', '200.00')
            ->assertJsonPath('data.paid_amount', '150.00')
            ->assertJsonPath('data.unpaid_amount', '50.00')
            ->assertJsonPath('data.transaction_count', 2);
    }
}
