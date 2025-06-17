<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\GeneratorExpense;
use App\Models\Generator;
use App\Models\Types;

class GeneratorExpenseFactory extends Factory
{
    protected $model = GeneratorExpense::class;

    public function definition(): array
    {
        return [
            'generator_id' => Generator::factory(),
            'type_id' => Types::factory(),
            'which' => $this->faker->word,
            'total' => $this->faker->randomFloat(0, 50000, 2000000),
            'paid' => $this->faker->randomFloat(0, 0, 2000000),
            'date' => $this->faker->date(),
            'status' => $this->faker->randomElement(['paid', 'loan']),
        ];
    }
}
