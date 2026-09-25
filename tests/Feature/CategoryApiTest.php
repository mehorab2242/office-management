<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_delete_used_category_without_deleting_expenses(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin);

        $id = $this->postJson('/api/categories', ['name' => 'Utilities'])
            ->assertCreated()->assertJsonPath('data.name', 'Utilities')->json('data.id');

        $this->putJson("/api/categories/{$id}", ['name' => 'Office Utilities'])
            ->assertOk()->assertJsonPath('data.name', 'Office Utilities');

        $expense = Expense::factory()->create(['category_id' => $id]);
        $softDeletedExpense = Expense::factory()->create(['category_id' => $id]);
        $softDeletedExpense->delete();
        $this->deleteJson("/api/categories/{$id}")->assertOk();

        $this->assertDatabaseMissing('categories', ['id' => $id]);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'category_id' => null]);
        $this->assertDatabaseHas('expenses', ['id' => $softDeletedExpense->id, 'category_id' => null]);
        $this->assertDatabaseHas('audit_logs', ['subject_type' => 'Category', 'subject_id' => $id, 'action' => 'category.deleted']);
        $this->getJson("/api/categories/{$id}")->assertNotFound();
    }

    public function test_staff_can_read_but_cannot_change_categories(): void
    {
        $staff = User::factory()->staff()->create();
        $category = Category::factory()->create();

        $this->actingAs($staff)->getJson('/api/categories')->assertOk();
        $this->postJson('/api/categories', ['name' => 'New'])->assertForbidden();
        $this->deleteJson("/api/categories/{$category->id}")->assertForbidden();
    }

    public function test_duplicate_category_name_is_rejected(): void
    {
        Category::factory()->create(['name' => 'Utilities']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->postJson('/api/categories', ['name' => 'Utilities'])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
    }
}
