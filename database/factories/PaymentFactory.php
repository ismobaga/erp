<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::query()->inRandomOrder()->value('id') ?? Client::factory(),
            'payment_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'amount' => fake()->randomFloat(2, 5000, 250000),
            'payment_method' => fake()->randomElement(['cash', 'bank_transfer', 'mobile_money']),
            'mobile_money_operator' => fake()->randomElement(['Orange Money', 'Moov Money', null]),
            'reference' => strtoupper(fake()->bothify('PAY-####??')),
            'notes' => fake()->sentence(),
            'allow_overpayment' => false,
            'is_flagged' => false,
        ];
    }
}
