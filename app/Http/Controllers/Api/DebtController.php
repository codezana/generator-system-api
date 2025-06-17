<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{User, Generator, Ampere, Expense, GeneratorExpense, Debt};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DebtController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ampere_id' => 'nullable|exists:ampere,id',
            'expense_id' => 'nullable|exists:expenses,id',
            'geexpense_id' => 'nullable|exists:generator_expenses,id',
            'paid' => 'required|numeric|min:1',
            'due_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => collect($validator->errors()->all())->first()
            ], 422);
        }

        $validated = $validator->validated();
        $paidAmount = $validated['paid'];

        // Fetch related records
        $expense = isset($validated['expense_id']) ? Expense::find($validated['expense_id']) : null;
        $generator_expense = isset($validated['geexpense_id']) ? GeneratorExpense::find($validated['geexpense_id']) : null;
        $paid_ampere = isset($validated['ampere_id']) ? Ampere::find($validated['ampere_id']) : null;
        $validated['user_id'] = Auth::user()->id;

        // Check if the paid amount exceeds the remaining balance
        if ($expense && $paidAmount > ($expense->total - $expense->paid)) {
            return response()->json([
                'error' => 'ناتوانیت بڕی دانەوەی قەرز زیاتر لە پارەدانی دراو بۆ خەرجی'
            ], 422);
        }

        if ($generator_expense && $paidAmount > ($generator_expense->total - $generator_expense->paid)) {
            return response()->json([
                'error' => 'ناتوانیت بڕی دانەوەی قەرز زیاتر لە پارەدانی دراو بۆ خەرجی مۆلیدە'
            ], 422);
        }

        if ($paid_ampere && $paidAmount > ($paid_ampere->total - $paid_ampere->paid)) {
            return response()->json([
                'error' => 'ناتوانیت بڕی دانەوەی قەرز زیاتر لە پارەدانی دراو بۆ ئەمپیەر'
            ], 422);
        }

        // Update paid amounts
        if ($expense) {
            $expense->increment('paid', $paidAmount);
            if ($expense->paid >= $expense->total) {
                $expense->update(['status' => 'paid']);
            }
        }

        if ($generator_expense) {
            $generator_expense->increment('paid', $paidAmount);
            if ($generator_expense->paid >= $generator_expense->total) {
                $generator_expense->update(['status' => 'paid']);
            }
        }

        if ($paid_ampere) {
            $paid_ampere->increment('paid', $paidAmount);
            if ($paid_ampere->paid >= $paid_ampere->total) {
                $paid_ampere->update(['status' => 'paid']);
            }
        }

        // Create the debt record
        $debt = Debt::create($validated);

        // Load relationships
        $debt->load('ampere', 'expense.expenseType', 'generator_expense');

        return response()->json([
            'message' => 'قەرز بە سەرکەوتوویی دروست کرا',
            'debt' => $debt
        ], 201);
    }
    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'ampere_id' => 'exists:ampere,id',
            'expense_id' => 'exists:expenses,id',
            'geexpense_id' => 'exists:generator_expenses,id',
            'paid' => 'numeric|min:1',
            'due_date' => 'date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => collect($validator->errors()->all())->first()
            ], 422);
        }

        $validated = $validator->validated();
        $newPaid = $validated['paid'];

        try {
            // Find the existing debt record
            $debt = Debt::findOrFail($id);
            $oldPaid = $debt->paid;
            $paidDifference = $newPaid - $oldPaid;

            // Fetch related records (only if IDs are provided)
            $expense = $debt->expense_id ? Expense::findOrFail($debt->expense_id) : null;
            $generator_expense = $debt->geexpense_id ? GeneratorExpense::findOrFail($debt->geexpense_id) : null;
            $paid_ampere = $debt->ampere_id ? Ampere::findOrFail($debt->ampere_id) : null;
            $validated['user_id'] = Auth::user()->id;

            // Check if new payment exceeds allowed balance **(only for increasing payments)**
            if ($paidDifference > 0) {
                if ($expense && ($expense->paid + $paidDifference) > $expense->total) {
                    return response()->json(['error' => 'ناتوانیت بڕی دانەوەی قەرز زیاتر بێت لە پارەدانی دراو لە خەرجی'], 422);
                }
                if ($generator_expense && ($generator_expense->paid + $paidDifference) > $generator_expense->total) {
                    return response()->json(['error' => 'ناتوانیت بڕی دانەوەی قەرز زیاتر بێت لە پارەدانی دراو لە خەرجی مۆلیدە'], 422);
                }
                if ($paid_ampere && ($paid_ampere->paid + $paidDifference) > $paid_ampere->total) {
                    return response()->json(['error' => 'ناتوانیت بڕی دانەوەی قەرز زیاتر بێت لە پارەدانی دراو لە ئەمپیەر'], 422);
                }
            }

            // Adjust paid amounts **(ensuring valid balance update)**
            if ($paidDifference > 0) {
                if ($expense) $expense->increment('paid', $paidDifference);
                if ($generator_expense) $generator_expense->increment('paid', $paidDifference);
                if ($paid_ampere) $paid_ampere->increment('paid', $paidDifference);
            } elseif ($paidDifference < 0) {
                if ($expense && $expense->paid >= abs($paidDifference)) {
                    $expense->decrement('paid', abs($paidDifference));
                }
                if ($generator_expense && $generator_expense->paid >= abs($paidDifference)) {
                    $generator_expense->decrement('paid', abs($paidDifference));
                }
                if ($paid_ampere && $paid_ampere->paid >= abs($paidDifference)) {
                    $paid_ampere->decrement('paid', abs($paidDifference));
                }
            }

            // Update debt record
            $debt->update($validated);

            // Update status (only for sent fields)
            if ($expense) {
                $expense->update(['status' => $expense->paid >= $expense->total ? 'paid' : 'loan']);
            }
            if ($generator_expense) {
                $generator_expense->update(['status' => $generator_expense->paid >= $generator_expense->total ? 'paid' : 'loan']);
            }
            if ($paid_ampere) {
                $paid_ampere->update(['status' => $paid_ampere->paid >= $paid_ampere->total ? 'paid' : 'loan']);
            }

            // Reload relationships
            $debt->load('ampere', 'expense.expenseType', 'generator_expense');

            return response()->json([
                'message' => 'قەرز بە سەرکەوتوویی نوێ کرایەوە',
                'debt' => $debt
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'نەتوانرا قەرز نوێ بکرێتەوە'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(string $id)
    {
        try {
            $debt = Debt::findOrFail($id);

            // Fetch related records safely
            $expense = $debt->expense_id ? Expense::find($debt->expense_id) : null;
            $generator_expense = $debt->geexpense_id ? GeneratorExpense::find($debt->geexpense_id) : null;
            $paid_ampere = $debt->ampere_id ? Ampere::find($debt->ampere_id) : null;

            // Subtract paid amount before deleting
            if ($expense) {
                $expense->decrement('paid', $debt->paid);
                $expense->update(['status' => $expense->paid < $expense->total ? 'loan' : 'paid']);
            }

            if ($generator_expense) {
                $generator_expense->decrement('paid', $debt->paid);
                $generator_expense->update(['status' => $generator_expense->paid < $generator_expense->total ? 'loan' : 'paid']);
            }

            if ($paid_ampere) {
                $paid_ampere->decrement('paid', $debt->paid);
                $paid_ampere->update(['status' => $paid_ampere->paid < $paid_ampere->total ? 'loan' : 'paid']);
            }

            // Delete the debt record
            $debt->delete();

            return response()->json([
                'message' => 'قەرز بە سەرکەوتوویی سڕایەوە'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'نەتوانرا قەرز بسڕدرێتەوە'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */

    public function show($id)
    {
        $debt = Debt::findOrFail($id);
        return response()->json($debt);
    }

    public function debt(Request $request)
    {
        if ($request->ampere_id) {

            $debt = Debt::where('ampere_id', $request->ampere_id)->get();

            if ($debt->isEmpty()) {
                return response()->json(['error' => 'قەرز نەدۆزراوەتەوە'], 404);
            }
            $details = $debt->map(function ($debt) {
                return [
                    'id' => $debt->id,
                    'created_by' => $debt->user->name,
                    'generator' => $debt->ampere->generator->name,
                    'capacity' => $debt->ampere->generator->ampere,
                    'paid' => $debt->paid,
                    'remaining' => $debt->ampere->total - $debt->ampere->paid,
                    'status' => $debt->ampere->status,
                    'due_date' => $debt->due_date,
                    'created_at' => $debt->created_at,
                    'updated_at' => $debt->updated_at
                ];
            });
            return response()->json($details, 200);
        } elseif ($request->generator_expense_id) {
            $debt = Debt::where('geexpense_id', $request->generator_expense_id)->with('generator_expense.generator')->get();

            if ($debt->isEmpty()) {
                return response()->json(['error' => 'قەرز نەدۆزراوەتەوە'], 404);
            }
            $details = $debt->map(function ($debt) {
                return [
                    'id' => $debt->id,
                    'created_by' => $debt->user->name,
                    'generator' => $debt->generator_expense->generator->name,
                    'type' => $debt->generator_expense->generator->ampere,
                    'paid' => $debt->paid,
                    'remaining' => $debt->generator_expense->total - $debt->generator_expense->paid,
                    'status' => $debt->generator_expense->status,
                    'due_date' => $debt->due_date,
                    'created_at' => $debt->created_at,
                    'updated_at' => $debt->updated_at
                ];
            });
            return response()->json($details, 200);
        } elseif ($request->expense_id) {
            $debt = Debt::where('expense_id', $request->expense_id)->with('expense.expenseType')->get();
            Log::info($debt);

            if ($debt->isEmpty()) {
                return response()->json(['error' => 'قەرز نەدۆزراوەتەوە'], 404);
            }
            $details = $debt->map(function ($debts) {
                return [
                    'id' => $debts->id,
                    'created_by' => $debts->user->name,
                    'type' => $debts->expense->expenseType->name,
                    'paid' => $debts->paid,
                    'remaining' => $debts->expense->total - $debts->expense->paid,
                    'status' => $debts->expense->status,
                    'due_date' => $debts->due_date,
                    'created_at' => $debts->created_at,
                    'updated_at' => $debts->updated_at
                ];
            });
            return response()->json($details, 200);
        } else {
            return response()->json([
                'error' => 'هیچ شتێک نەدۆزراوەتەوە'
            ], 404);
        }
    }
}
