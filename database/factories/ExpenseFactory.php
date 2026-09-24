<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'period_month' => '2026-09-01',
            'expense_date' => '2026-09-15',
            'description' => fake()->words(3, true),
            'amount' => 100.00,
            'payment_status' => null,
            'category_id' => Category::factory(),
            'created_by' => User::factory(),
        ];
    }
}
