<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{User, Generator, Expense, GeneratorExpense, Debt, Ampere, Types};
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class IndexController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get the current month's start and end date
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        // Get data based on role
        switch ($user->role) {
            case 'manager':
                $generators = Generator::where('manager_id', $user->id)->get();
                if ($generators->isEmpty()) return response()->json(['message' => 'No generators found for this manager.'], 404);
                break;
    
            case 'admin':
                $generators = Generator::where('admin_id', $user->id)->get();
                if ($generators->isEmpty()) return response()->json(['message' => 'No generators found for this Admin.'], 404);
                break;
    
            case 'super_admin':
                $generators = Generator::all();
                if ($generators->isEmpty()) return response()->json(['message' => 'No generators found.'], 404);
                break;
    
            default:
                return response()->json(['message' => 'Unauthorized role.'], 403);
        }
    
        $generatorIds = $generators->pluck('id');
        $generatorExpenses = $this->getGeneratorExpenses($generatorIds, $startDate, $endDate);
        $ampereExpenses = $this->getAmpereExpenses($generatorIds, $startDate, $endDate);    
        return response()->json([
            'generator_expenses' => $generatorExpenses,
            'ampere' => $ampereExpenses,
            'expenses' => $this->getExpenseDetails($startDate, $endDate)
        ]);
    }
    
    private function getGeneratorExpenses($generatorIds, $startDate, $endDate)
    {
        $expenses = GeneratorExpense::whereIn('generator_id', $generatorIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
    
        $sumPaid = $expenses->sum('paid');
        $sumTotal = $expenses->sum('total');
        $sumDebt = $sumTotal - $sumPaid;
        $repayment = Debt::whereIn('geexpense_id', $expenses->pluck('id'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('paid');
    
        return [
            'total'=> $sumTotal,
            'paid'=> $sumPaid,
            'debt' => $sumDebt,
            'repayment' => $repayment,
        ];
    }
    
    private function getAmpereExpenses($generatorIds, $startDate, $endDate)
    {
        $ampere = Ampere::whereIn('generator_id', $generatorIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
    
        $sumPaid = $ampere->sum('paid');
        $sumTotal = $ampere->sum('total');
        $sumDebt = $sumTotal - $sumPaid;
        $repayment = Debt::whereIn('ampere_id', $ampere->pluck('id'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('paid');
    
        return [
            'total'=> $sumTotal, 
            'paid'=> $sumPaid,
            'debt' => $sumDebt,
            'repayment' => $repayment,
        ];
    }
    public function getRepayment()
    {
        // Get the current month's start and end date
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        // Get the authenticated user
        $user = Auth::user();
        
        // Fetch the relevant generators based on the user's role
        switch ($user->role) {
            case 'manager':
                $generators = Generator::where('manager_id', $user->id)->get();
                if ($generators->isEmpty()) return response()->json(['message' => 'No generators found for this manager.'], 404);
                break;
    
            case 'admin':
                $generators = Generator::where('admin_id', $user->id)->get();
                if ($generators->isEmpty()) return response()->json(['message' => 'No generators found for this Admin.'], 404);
                break;
    
            case 'super_admin':
                $generators = Generator::all();
                if ($generators->isEmpty()) return response()->json(['message' => 'No generators found.'], 404);
                break;
    
            default:
                return response()->json(['message' => 'Unauthorized role.'], 403);
        }
    
        // Get generator IDs
        $generatorIds = $generators->pluck('id');
        
        // Query Debt within the current month
        $query = Debt::whereBetween('created_at', [$startDate, $endDate]);
    
        // Apply specific logic based on the user's role
        switch ($user->role) {
            case 'super_admin':
                // Super admin can see all repayments
                $repayment = $query->with('user', 'ampere', 'generator_expense', 'expense')->get();
                break;
    
            case 'manager':
                // Manager can see expense, ampere, and generator expense repayments
                $repayment = $query->whereHas('generator_expense', function($q) use ($generatorIds) {
                    $q->whereIn('generator_id', $generatorIds);
                })->orWhereHas('ampere', function($q) use ($generatorIds) {
                    $q->whereIn('generator_id', $generatorIds);
                })->with('user', 'ampere', 'generator_expense', 'expense')->get();
                break;
    
            case 'admin':
                // Admin can see only ampere and generator expense repayments
                $repayment = $query->whereHas('generator_expense', function($q) use ($generatorIds) {
                    $q->whereIn('generator_id', $generatorIds);
                })->orWhereHas('ampere', function($q) use ($generatorIds) {
                    $q->whereIn('generator_id', $generatorIds);
                })->with('user', 'ampere', 'generator_expense')->get();
                break;
    
            default:
                return response()->json(['message' => 'Unauthorized role.'], 403);
        }
    
        // Return detailed repayment data
        return response()->json([
            'repayment_data' => $repayment
        ]);
    }
    
    
    private function getExpenseDetails($startDate, $endDate)
    {
        $user = Auth::user();
        if($user->role == 'super_admin') {
            // Super admin can see all expenses
            $expenses = Expense::whereBetween('created_at', [$startDate, $endDate])->get();
        } else {
            // Admins and Managers see only their expenses
            $expenses = Expense::where('made', $user->id)->whereBetween('created_at', [$startDate, $endDate])->get();
        }

        // Sum up the total, paid, debt, and repayment
        $sumExpense = $expenses->sum('total');
        $sumPaid = $expenses->sum('paid');
        $sumDebt = $sumExpense - $sumPaid;
        $repayment = Debt::whereIn('expense_id', $expenses->pluck('id'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('paid');
    
        return [
            'total' => $sumExpense,
            'paid' => $sumPaid,
            'debt' => $sumDebt,
            'repayment' => $repayment,
        ];
    }
}
