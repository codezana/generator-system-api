<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\GeneratorExpense;
use App\Models\Ampere;

class DebtFactory extends Factory
{
    protected $model = Debt::class;

    public function definition(): array
    {
        return [
            'expense_id' => Expense::factory(),
            'geexpense_id' => GeneratorExpense::factory(),
            'ampere_id' => Ampere::factory(),
            'paid' => $this->faker->randomFloat(0, 100000, 500000),
            'due_date' => $this->faker->date(),
        ];
    }
}
