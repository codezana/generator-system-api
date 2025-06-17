<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Generator;
use App\Models\User;

class GeneratorFactory extends Factory
{
    protected $model = Generator::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'admin_id' => User::factory(),
            'manager_id' => User::factory(),
            'location' => $this->faker->address,
            'ampere' => $this->faker->randomFloat(0, 50, 200),
        ];
    }
}
