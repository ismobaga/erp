<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'category' => fake()->randomElement(['travel', 'supplies', 'operations', 'payroll', 'other']),
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 5000, 220000),
            'expense_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'payment_method' => fake()->randomElement(['cash', 'bank_transfer', 'mobile_money']),
            'vendor' => fake()->company(),
            'reference' => strtoupper(fake()->bothify('EXP-####??')),
            'approval_status' => fake()->randomElement(['pending', 'approved', 'review']),
        ];
    }
}
