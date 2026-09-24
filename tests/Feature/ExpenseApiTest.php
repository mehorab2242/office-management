<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_an_expense_with_exact_date_and_payer_allocation(): void
    {
        $staff = User::factory()->staff()->create();
        $category = Category::factory()->create();

        $this->actingAs($staff)->postJson('/api/expenses', [
            'expense_date' => '2026-09-17',
            'description' => 'Internet bill',
            'amount' => '3150.00',
            'category_id' => $category->id,
            'payment_status' => 'paid',
            'payment_method' => 'Cash',
            'payer_allocations' => [['payer_name' => 'Office cashier', 'amount' => '3150.00']],
        ])->assertCreated()->assertJsonPath('data.period_month', '2026-09-01')
            ->assertJsonPath('data.payer_allocations.0.payer_name', 'Office cashier');

        $this->assertDatabaseHas('expenses', ['description' => 'Internet bill', 'created_by' => $staff->id]);
        $this->assertDatabaseHas('expense_payer_allocations', ['payer_name' => 'Office cashier', 'amount' => 3150]);
    }

    public function test_undated_expense_requires_a_period_and_preserves_null_date(): void
    {
        $this->actingAs(User::factory()->admin()->create())->postJson('/api/expenses', [
            'period_month' => '2026-04-01',
            'description' => 'UPS',
            'amount' => '2000.00',
        ])->assertCreated()->assertJsonPath('data.expense_date', null);

        $this->assertDatabaseHas('expenses', ['description' => 'UPS', 'expense_date' => null]);
    }

    public function test_invalid_amount_and_mismatched_payer_total_are_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create())->postJson('/api/expenses', [
            'expense_date' => '2026-09-17',
            'description' => 'Internet',
            'amount' => '-10.00',
        ])->assertUnprocessable()->assertJsonValidationErrors('amount');

        $this->postJson('/api/expenses', [
            'expense_date' => '2026-09-17',
            'description' => 'Internet',
            'amount' => '100.00',
            'payer_allocations' => [['payer_name' => 'Alice', 'amount' => '50.00']],
        ])->assertUnprocessable()->assertJsonValidationErrors('payer_allocations');
    }

    public function test_staff_can_update_own_expense_but_not_anothers_and_only_admin_can_delete(): void
    {
        $owner = User::factory()->staff()->create();
        $other = User::factory()->staff()->create();
        $expense = Expense::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($other)->putJson("/api/expenses/{$expense->id}", ['description' => 'Changed'])
            ->assertForbidden();

        $this->actingAs($owner)->putJson("/api/expenses/{$expense->id}", [
            'description' => 'Updated cost',
        ])->assertOk()->assertJsonPath('data.description', 'Updated cost');

        $this->deleteJson("/api/expenses/{$expense->id}")->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->deleteJson("/api/expenses/{$expense->id}")
            ->assertOk();
        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }

    public function test_list_filters_searches_sorts_and_paginates_in_database(): void
    {
        $category = Category::factory()->create();
        Expense::factory()->create([
            'category_id' => $category->id, 'description' => 'Internet September',
            'amount' => '200.00', 'payment_status' => 'paid',
        ]);
        Expense::factory()->create([
            'category_id' => $category->id, 'description' => 'Internet August',
            'period_month' => '2026-08-01', 'expense_date' => '2026-08-20',
            'amount' => '100.00', 'payment_status' => 'unpaid',
        ]);
        Expense::factory()->create(['description' => 'Tea', 'amount' => '50.00']);

        $this->actingAs(User::factory()->staff()->create())
            ->getJson("/api/expenses?search=Internet&category_id={$category->id}&payment_status=paid&date_from=2026-09-01&date_to=2026-09-30&per_page=1&sort=amount&direction=desc")
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Internet September')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_update_cannot_make_existing_payer_allocations_disagree_with_amount(): void
    {
        $user = User::factory()->admin()->create();
        $expense = Expense::factory()->create(['amount' => '100.00']);
        $expense->payerAllocations()->create(['payer_name' => 'Cashier', 'amount' => '100.00']);

        $this->actingAs($user)->putJson("/api/expenses/{$expense->id}", ['amount' => '150.00'])
            ->assertUnprocessable()->assertJsonValidationErrors('payer_allocations');
    }

    public function test_update_rejects_period_that_conflicts_with_existing_expense_date(): void
    {
        $expense = Expense::factory()->create([
            'expense_date' => '2026-09-15', 'period_month' => '2026-09-01',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->putJson("/api/expenses/{$expense->id}", ['period_month' => '2026-08-01'])
            ->assertUnprocessable()->assertJsonValidationErrors('period_month');
    }

    public function test_expense_write_actions_create_audit_records(): void
    {
        $admin = User::factory()->admin()->create();
        $expense = Expense::factory()->create(['created_by' => $admin->id]);
        $this->actingAs($admin)->putJson("/api/expenses/{$expense->id}", ['amount' => '125.00'])->assertOk();
        $this->deleteJson("/api/expenses/{$expense->id}")->assertOk();

        $this->assertDatabaseHas('audit_logs', ['subject_type' => 'Expense', 'subject_id' => $expense->id, 'action' => 'expense.updated']);
        $this->assertDatabaseHas('audit_logs', ['subject_type' => 'Expense', 'subject_id' => $expense->id, 'action' => 'expense.deleted']);
    }
}
