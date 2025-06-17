<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Users Table (Super Admin, Managers, Generator Admins)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('password');
            $table->enum('role', ['super_admin', 'manager', 'admin']); // Role-based access
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete(); // Only for generator_admins
            $table->timestamps();
        });

        DB::table('users')->insert([
            'name' => 'super admin',
            'password' => Hash::make('super@super'),
            'role' => 'super_admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Generators Table (Managed by a Manager and assigned to a Generator Admin)
        Schema::create('generators', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Generator Name/Identifier
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete(); // Generator Admin
            $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete(); // Manager overseeing this generator
            $table->string('location'); // Location of the generator
            $table->decimal('ampere', 20, 0); // Ampere capacity
            $table->timestamps();
        });

        // Generator Monthly Stats Table (Tracks generator usage and pricing per month)
        Schema::create('ampere', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generator_id')->constrained('generators')->cascadeOnDelete();
            $table->date('date'); // Month number (1-20)
            $table->integer('total_hours'); // Total hours worked in the month
            $table->decimal('hourly_price', 20, 0); // Price per hour (e.g., 44 IQD)
            $table->decimal('final', 20, 0); // Final price per ampere
            $table->decimal('total', 20, 0); // 2 million
            $table->decimal('paid', 20, 0)->nullable(); // 1 million qarz
            $table->enum('status', ['paid', 'loan']); // Payment status
            $table->timestamps();
        });

        // Expense Types Table (Stores different expense types dynamically)
        Schema::create('types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., fuel, oil, filter, etc.
            $table->timestamps();
        });

        // Expenses Table (Purchases made by Managers for Generators)
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_type_id')->constrained('types')->cascadeOnDelete(); // Links to Expense Types Table
            $table->foreignId('made')->constrained('users')->cascadeOnDelete(); // Manager who made the purchase
            $table->string('description')->nullable(); // e.g., 20 litr
            $table->decimal('price', 20, 0); // price of expense
            $table->integer('quantity')->nullable(); // Liters, Barrels, or Units
            $table->decimal('total', 20, 0);
            $table->decimal('paid', 20, 0)->nullable();
            $table->string('invoice_number')->nullable();
            $table->date('date');
            $table->enum('status', ['paid', 'loan']); // Payment status
            $table->timestamps();
        });

        // Expense of generator table
        Schema::create('generator_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generator_id')->constrained('generators')->cascadeOnDelete();
            $table->foreignId('type_id')->constrained('types')->cascadeOnDelete(); // rent of generator
            $table->text('which')->nullable();
            $table->decimal('total', 20, 0);
            $table->decimal('paid', 20, 0)->nullable();
            $table->date('date');
            $table->enum('status', ['paid', 'loan']);
            $table->timestamps();
        });

        // Debts Table (For Loans & Unpaid Amounts)
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->cascadeOnDelete();
            $table->foreignId('geexpense_id')->nullable()->constrained('generator_expenses')->cascadeOnDelete();
            $table->foreignId('ampere_id')->nullable()->constrained('ampere')->cascadeOnDelete();
            $table->decimal('paid', 20, 0)->default(0.00); // Partial payments
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // User who owes money
            $table->date('due_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debts');
        Schema::dropIfExists('generator_expenses');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('types');
        Schema::dropIfExists('ampere');    
        Schema::dropIfExists('generators');
        Schema::dropIfExists('users');
    }
};
