<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            return;
        }

        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@example.test'],
            ['name' => 'Office Admin', 'password' => 'OfficeDemo123!', 'role' => User::ROLE_SUPER_ADMIN, 'is_active' => true],
        );
        User::updateOrCreate(
            ['email' => 'staff@example.test'],
            ['name' => 'Office Staff', 'password' => 'OfficeDemo123!', 'role' => User::ROLE_STAFF, 'is_active' => true],
        );

        foreach (['Utilities', 'Office Supplies', 'Pantry', 'Repairs'] as $name) {
            Category::firstOrCreate(['name' => $name], ['is_active' => true]);
        }

        foreach ([
            ['2026-09-04', 'Sample internet service', '3150.00', 'paid', 'Utilities'],
            ['2026-09-08', 'Sample stationery purchase', '480.00', 'unpaid', 'Office Supplies'],
            ['2026-09-12', 'Sample office snacks', '260.00', 'pending', 'Pantry'],
        ] as [$date, $description, $amount, $status, $categoryName]) {
            if (! Expense::query()->whereDate('expense_date', $date)->where('description', $description)->exists()) {
                Expense::create([
                    'description' => $description,
                    'expense_date' => $date,
                    'amount' => $amount,
                    'payment_status' => $status,
                    'category_id' => Category::where('name', $categoryName)->value('id'),
                    'created_by' => $superAdmin->id,
                    'updated_by' => $superAdmin->id,
                ]);
            }
        }
    }
}
