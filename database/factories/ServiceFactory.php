<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'code' => sprintf('SVC-%04d', fake()->unique()->numberBetween(1000, 9999)),
            'name' => fake()->jobTitle(),
            'category' => fake()->randomElement(['technology', 'support', 'logistics', 'consulting']),
            'description' => fake()->sentence(),
            'default_price' => fake()->randomFloat(2, 5000, 300000),
            'is_active' => true,
        ];
    }
}
