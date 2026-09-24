<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_user_and_staff_management_endpoints(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $staff = User::factory()->staff()->create();

        $this->actingAs($superAdmin)->getJson('/api/users')->assertOk();
        $this->actingAs($superAdmin)->getJson("/api/users/{$staff->id}")->assertOk();

        $this->actingAs($superAdmin)->postJson('/api/users', [
            'name' => 'New Staff',
            'email' => 'new-staff@example.test',
            'password' => 'long-password-123',
            'password_confirmation' => 'long-password-123',
            'role' => User::ROLE_STAFF,
        ])->assertCreated()->assertJsonPath('data.role', User::ROLE_STAFF);

        $this->actingAs($superAdmin)->putJson("/api/users/{$staff->id}", ['name' => 'Renamed Staff'])
            ->assertOk()->assertJsonPath('data.name', 'Renamed Staff');
    }

    public function test_staff_receives_403_when_attempting_user_management(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->getJson('/api/users')->assertForbidden();
        $this->actingAs($staff)->getJson("/api/users/{$superAdmin->id}")->assertForbidden();

        // Staff cannot create staff or super admin accounts.
        $this->actingAs($staff)->postJson('/api/users', [
            'name' => 'Another Staff',
            'email' => 'another@example.test',
            'password' => 'long-password-123',
            'role' => User::ROLE_STAFF,
        ])->assertForbidden();

        $this->actingAs($staff)->postJson('/api/users', [
            'name' => 'Sneaky Admin',
            'email' => 'sneaky@example.test',
            'password' => 'long-password-123',
            'role' => User::ROLE_SUPER_ADMIN,
        ])->assertForbidden();

        // Staff cannot change roles or permissions.
        $this->actingAs($staff)->putJson("/api/users/{$superAdmin->id}", ['role' => User::ROLE_STAFF])
            ->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'another@example.test']);
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'role' => User::ROLE_SUPER_ADMIN]);
    }

    public function test_staff_receives_403_for_super_admin_only_endpoints(): void
    {
        $staff = User::factory()->staff()->create();
        $expense = Expense::factory()->create(['created_by' => User::factory()->superAdmin()->create()->id]);

        $this->actingAs($staff)->postJson('/api/categories', ['name' => 'Utilities'])->assertForbidden();
        $this->actingAs($staff)->postJson('/api/imports/analyze')->assertForbidden();
        $this->actingAs($staff)->getJson('/api/audit-logs')->assertForbidden();

        // Staff cannot edit a super admin's expense or delete any expense.
        $this->actingAs($staff)->putJson("/api/expenses/{$expense->id}", ['description' => 'Nope'])
            ->assertForbidden();
        $this->actingAs($staff)->deleteJson("/api/expenses/{$expense->id}")->assertForbidden();
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'description' => $expense->description]);
    }

    public function test_staff_can_view_create_and_edit_own_expenses(): void
    {
        $staff = User::factory()->staff()->create();
        $own = Expense::factory()->create(['created_by' => $staff->id]);

        $this->actingAs($staff)->getJson('/api/expenses')->assertOk();

        $this->actingAs($staff)->postJson('/api/expenses', [
            'expense_date' => '2026-09-20',
            'description' => 'Stationery refill',
            'amount' => '450.00',
        ])->assertCreated()->assertJsonPath('data.created_by.id', $staff->id);

        $this->actingAs($staff)->putJson("/api/expenses/{$own->id}", ['description' => 'Updated by owner'])
            ->assertOk()->assertJsonPath('data.description', 'Updated by owner');
    }

    public function test_staff_can_view_reports_dashboard_and_exports(): void
    {
        $staff = User::factory()->staff()->create();
        Expense::factory()->create(['period_month' => '2026-09-01', 'expense_date' => '2026-09-10']);

        $this->actingAs($staff)->getJson('/api/dashboard')->assertOk();
        $this->actingAs($staff)->getJson('/api/reports/monthly?year=2026&month=9')->assertOk();
        $this->actingAs($staff)->getJson('/api/reports/yearly?year=2026')->assertOk();
        $this->actingAs($staff)->getJson('/api/reports/custom?date_from=2026-09-01&date_to=2026-09-30')->assertOk();
        $this->actingAs($staff)->getJson('/api/exports/expenses.xlsx')->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->actingAs($staff)->getJson('/api/exports/monthly.xlsx?year=2026&month=9')->assertOk();
    }

    public function test_super_admin_retains_access_to_all_expense_management_features(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $other = Expense::factory()->create(['created_by' => User::factory()->staff()->create()->id]);

        $this->actingAs($superAdmin)->putJson("/api/expenses/{$other->id}", ['description' => 'Corrected'])
            ->assertOk()->assertJsonPath('data.description', 'Corrected');
        $this->actingAs($superAdmin)->deleteJson("/api/expenses/{$other->id}")->assertOk();
        $this->assertSoftDeleted('expenses', ['id' => $other->id]);
    }

    public function test_legacy_admin_role_is_upgraded_to_super_admin_by_the_migration(): void
    {
        $user = User::factory()->superAdmin()->create();
        $migration = 'database/migrations/2026_09_24_094457_rename_admin_role_to_super_admin.php';

        $this->artisan('migrate:rollback', ['--path' => $migration]);
        $this->assertSame('admin', $user->fresh()->role);
        $this->assertFalse($user->fresh()->isSuperAdmin());

        $this->artisan('migrate', ['--path' => $migration]);
        $this->assertSame('super_admin', $user->fresh()->role);
        $this->assertTrue($user->fresh()->isSuperAdmin());
    }
}
