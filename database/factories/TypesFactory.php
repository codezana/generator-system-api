<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Types;

class TypesFactory extends Factory
{
    protected $model = Types::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
        ];
    }
}
