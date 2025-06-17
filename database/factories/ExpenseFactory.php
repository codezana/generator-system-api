<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Expense;
use App\Models\Types;
use App\Models\User;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'expense_type_id' => Types::factory(),
            'made' => User::factory(),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(0, 50000, 500000),
            'quantity' => $this->faker->randomNumber(2),
            'total' => $this->faker->randomFloat(0, 100000, 2000000),
            'paid' => $this->faker->randomFloat(0, 0, 2000000),
            'invoice_number' => $this->faker->unique()->numerify('INV###'),
            'date' => $this->faker->date(),
            'status' => $this->faker->randomElement(['paid', 'loan']),
        ];
    }
}
