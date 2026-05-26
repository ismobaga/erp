<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $issueDate = fake()->dateTimeBetween('-8 months', '-2 days');
        $subtotal = fake()->randomFloat(2, 25000, 650000);
        $taxTotal = round($subtotal * 0.18, 2);
        $total = $subtotal + $taxTotal;

        return [
            'client_id' => Client::query()->inRandomOrder()->value('id') ?? Client::factory(),
            'issue_date' => $issueDate,
            'due_date' => (clone $issueDate)->modify('+15 days'),
            'status' => fake()->randomElement(['draft', 'sent']),
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'tax_total' => $taxTotal,
            'total' => $total,
            'credit_total' => 0,
            'paid_total' => 0,
            'balance_due' => $total,
            'notes' => fake()->sentence(),
        ];
    }
}
