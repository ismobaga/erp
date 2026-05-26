<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-4 months', '+2 weeks');

        return [
            'client_id' => Client::query()->inRandomOrder()->value('id') ?? Client::factory(),
            'service_id' => Service::query()->inRandomOrder()->value('id') ?? Service::factory(),
            'name' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['planned', 'in_progress', 'on_hold']),
            'approval_status' => fake()->randomElement(['pending', 'approved']),
            'start_date' => $startDate,
            'due_date' => (clone $startDate)->modify('+45 days'),
            'notes' => fake()->sentence(),
        ];
    }
}
