<?php

namespace Database\Seeders;

use App\Models\{User, Ampere, Debt, Expense, Generator, GeneratorExpense, Types};
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
         User::factory(10)->create();
         Ampere::factory()->count(10)->create();
         Debt::factory()->count(10)->create();
         Expense::factory()->count(10)->create();
         Generator::factory()->count(10)->create();
         GeneratorExpense::factory()->count(10)->create();
         Types::factory()->count(10)->create();
    }
}
