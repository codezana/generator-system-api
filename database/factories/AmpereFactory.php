<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Ampere;
use App\Models\Generator;

class AmpereFactory extends Factory
{
    protected $model = Ampere::class;

    public function definition(): array
    {
        return [
            'generator_id' => Generator::factory(),
            'date' => $this->faker->date(),
            'total_hours' => $this->faker->numberBetween(50, 200),
            'hourly_price' => $this->faker->randomFloat(0, 40, 60),
            'final' => $this->faker->randomFloat(0, 100000, 500000),
            'total' => $this->faker->randomFloat(0, 500000, 2000000),
            'paid' => $this->faker->randomFloat(0, 0, 2000000),
            'status' => $this->faker->randomElement(['paid', 'loan']),
        ];
    }
}
