<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Generator, Ampere, Expense, User, Debt, GeneratorExpense};
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReportsController extends Controller
{
    public function generatorNameByUserRole()
    {
        // Get the authenticated user
        $user = Auth::user();

        // Use the user to get generators based on their role
        if ($user->role === 'super_admin') {
            // Super admins can see all generators
            $generators = Generator::select('id', 'name')->get();
            $users = User::select('id', 'name')->where('role', 'manager')->get();
        } elseif ($user->role === 'manager') {
            // Managers can see generators they manage
            $generators = Generator::select('id', 'name')->where('manager_id', $user->id)->get();
            
            $users = collect([
                [
                    'id' => $user->id,
                    'name' => $user->name
                ]
            ]);

        } elseif ($user->role === 'admin') {
            // Admins can see their assigned generators
            $generators = Generator::select('id', 'name')->where('admin_id', $user->id)->get();
            $users = []; // Admin does not get users data
        } else {
            // Unauthorized role
            return response()->json(['message' => 'Unauthorized role.'], 403);
        }

        // Return the generators and users as a JSON response
        return response()->json([
            'generators' => $generators,
            'users' => $users
        ]);
    }




    // Ampere usage report
    public function ampereUsageReport(Request $request)
    {
        // Get the generator_id and date filter from the request
        $generatorId = $request->input('generator_id');
        $datefillter = $request->input('date');

        // Validate that generator_id is provided and is either a single ID or an array
        if (!$generatorId) {
            return response()->json(['message' => 'Generator ID is required.'], 400);
        }

        // If generatorId is not an array, make it an array for the whereIn condition
        if (!is_array($generatorId)) {
            $generatorId = [$generatorId];
        }

        // Start the Ampere query with the related debts
        $ampereQuery = Ampere::whereIn('generator_id', $generatorId)
            ->with('debts');



        // If date is provided, filter by that month and year
        if ($datefillter) {
            $date = \Carbon\Carbon::parse($datefillter);
            $ampereQuery->whereYear('date', $date->year)
                ->whereMonth('date', $date->month);
        }

        // Get all amperes
        $amperes = $ampereQuery->get();

        // Calculate totals
        $total = $amperes->sum('total');
        $totalPaid = $amperes->sum('paid');
        $debt = $total - $totalPaid;
        $repayment = $amperes->flatMap(function ($ampere) {
            return $ampere->debts;
        })->sum('paid') ?? 0;



        $report = [
            'total' => $total,
            'total_paid' => $totalPaid,
            'debt' => $debt,
            'repayment' => $repayment,
        ];

        // Return the report as JSON
        return response()->json($report);
    }

    public function geExpenseUsageReport(Request $request)
    {
        // Get the generator_id and date filter from the request
        $generatorId = $request->input('generator_id');
        $datefillter = $request->input('date');

        // Validate that generator_id is provided and is either a single ID or an array
        if (!$generatorId) {
            return response()->json(['message' => 'Generator ID is required.'], 400);
        }

        // If generatorId is not an array, make it an array for the whereIn condition
        if (!is_array($generatorId)) {
            $generatorId = [$generatorId];
        }

        // Sanitize generator_id to ensure they are valid integers
        $generatorId = array_map('intval', $generatorId);

        // Start the geExpense query with the related generators and debts
        $geExpenseQuery = GeneratorExpense::whereIn('generator_id', $generatorId)
            ->with(['debts']);

        // If date is provided, filter by that month and year
        if ($datefillter) {
            $date = Carbon::parse($datefillter);
            $geExpenseQuery->whereYear('date', $date->year)
                ->whereMonth('date', $date->month);
        }

        // Execute the query
        $geExpenses = $geExpenseQuery->get();


        $total = $geExpenses->sum('total');
        $totalPaid = $geExpenses->sum('paid');
        $debt = $total - $totalPaid;
        $repayment = $geExpenses->flatMap(function ($geExpense) {
            return $geExpense->debts;
        })->sum('paid') ?? 0;

        $report = [
            'total' => $total,
            'total_paid' => $totalPaid,
            'debt' => $debt,
            'reapayment' => $repayment
        ];

        // Return the report as JSON
        return response()->json($report);
    }

    public function expenseUsageReport(Request $request)
    {
        // Get the user_id and date filter from the request
        $userid = $request->input('user_id');
        $datefillter = $request->input('date');

        // Validate that user_id is provided and is either a single ID or an array
        if (!$userid) {
            return response()->json(['message' => 'User ID is required.'], 400);
        }

        // If userid is not an array, make it an array for the whereIn condition
        if (!is_array($userid)) {
            $userid = [$userid];
        }

        // Sanitize user_id to ensure they are valid integers
        $userid = array_map('intval', $userid);

        // Start the expense query with the related expenses and debts
        $expenseQuery = Expense::whereIn('made', $userid)
            ->with(['debts']); // Join expense type, debts, and purchaser

        // If date is provided, filter by that month and year
        if ($datefillter) {
            $date = \Carbon\Carbon::parse($datefillter);
            $expenseQuery->whereYear('date', $date->year)
                ->whereMonth('date', $date->month);
        }

        // Execute the query
        $expenses = $expenseQuery->get();

        $total = $expenses->sum('total');
        $totalPaid = $expenses->sum('paid');
        $debt = $total - $totalPaid;
        $repayment = $expenses->flatMap(function ($expense) {
            return $expense->debts;
        })->sum('paid') ?? 0; // Safely handle null repayment sums

        $report = [
            'total' => $total,
            'total_paid' => $totalPaid,
            'debt' => $debt,
            'repayment' => $repayment
        ];
        // Return the report as JSON
        return response()->json($report);
    }
}
