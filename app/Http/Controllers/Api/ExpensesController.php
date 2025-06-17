<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ExpensesController extends Controller
{ /**
    * Display a listing of the resource.
    */
   public function index()
   {
       // Display expenses

       $expenses = Auth::user()->role == 'super_admin' ? Expense::with('expenseType', 'purchaser')->get() : Expense::where('made', Auth::user()->id)->with('expenseType', 'purchaser')->get() ;

       $details = $expenses->map(function ($expense) {
           return [
               'id' => $expense->id,
               'type' => $expense->expenseType->name,
               'description' => $expense->description,
               'made' => $expense->purchaser->name,
               'price' => $expense->price,
               'quantity' => $expense->quantity,
               'total' => $expense->total,
               'paid' => $expense->paid,
               'invoice_number' => $expense->invoice_number,
               'date' => $expense->date,
               'status' => $expense->status,
           ];
       });

       return response()->json($details);
   }

   /**
    * Store a newly created resource in storage.
    */
   public function store(Request $request)
   {

       // Validate and create expense
       $validator = Validator::make($request->all(), ([
           'expense_type_id' => 'required|exists:types,id',
           'description' => 'nullable|string',
           'price' => 'required|numeric',
           'quantity' => 'required|integer',
           'invoice_number' => 'nullable|string',
           'total' => 'required|numeric',
           'paid' => 'required|numeric',
           'date' => 'required|date'
               ]));

       if ($validator->fails()) {
           return response()->json([
               'error' => collect($validator->errors()->all())->first()
           ], 422);
       }

       $validateData = $validator->validated();

       // Create the expense
       $expense = Expense::create([
           'expense_type_id' => $validateData['expense_type_id'],
           'made' => Auth::user()->id,
           'description' => $validateData['description'],
           'price' => $validateData['price'],
           'quantity' => $validateData['quantity'],
           'total' => $validateData['total'],
           'paid' => $validateData['paid'],
           'invoice_number' => $validateData['invoice_number'],
           'date' => $validateData['date'],
           'status' => (float)$validateData['total'] === (float)$validateData['paid'] ? 'paid' : 'loan',
       ]);

       // Load relationships
       $expense->load(['expenseType', 'purchaser']);

       return response()->json([
           'message' => 'خەرجییەکان بە سەرکەوتوویی دروستکران',
           'expense' => $expense
       ], 201);
   }


   /**
    * Display the specified resource.
    */
   public function show($id)
   {
       // Show specific expense
       $expense = Auth::user()->role == 'super_admin' ? Expense::with('expenseType', 'purchaser')->findOrFail($id) : Expense::where('made', Auth::user()->id)->with('expenseType', 'purchaser')->findOrFail($id);

       $expense = [
           'id' => $expense->id,
           'type' => $expense->expenseType->name,
           'description' => $expense->description,
           'made' => $expense->purchaser->name,
           'price' => $expense->price,
           'quantity' => $expense->quantity,
           'total' => $expense->total,
           'paid' => $expense->paid,
           'invoice_number' => $expense->invoice_number,
           'date' => $expense->date,
           'status' => $expense->status,
       ];
       return response()->json($expense);
   }

   /**
    * Update the specified resource in storage.
    */
   public function update(Request $request,$id)
   {
       // Validate input
       $validated = Validator::make($request->all(), ([
           'expense_type_id' => 'exists:types,id',
           'description' => 'nullable|string',
           'price' => 'nullable|numeric',
           'quantity' => 'nullable|integer',
           'invoice_number' => 'nullable|string',
           'total' => 'nullable|numeric',
           'paid' => 'nullable|numeric',
           'date' => 'nullable|date',
       ]));

       if ($validated->fails()) {
           return response()->json([
               'error' => collect($validated->errors()->all())->first()
           ], 422);
       }
       // Find the expense
       $expense = Expense::findOrFail($id);

       $validateData = $validated->validated();
       // Update only provided fields
       $expense->update([
           'expense_type_id' => $validateData['expense_type_id'] ?? $expense->expense_type_id,
           'description' => $validateData['description'] ?? $expense->description,
           'price' => $validateData['price'] ?? $expense->price,
           'quantity' => $validateData['quantity'] ?? $expense->quantity,
           'total' => $validateData['total'] ?? $expense->total,
           'paid' => $validateData['paid'] ?? $expense->paid,
           'invoice_number' => $validateData['invoice_number'] ?? $expense->invoice_number,
           'date' => $validateData['date'] ?? $expense->date,
           'status' => (float)$validateData['total'] === (float)$validateData['paid'] ? 'paid' : 'loan' ?? $expense->status,
       ]);

       // Load relationships
       $expense->load(['expenseType', 'purchaser']);

       return response()->json([
           'message' => 'خەرجییەکان بە سەرکەوتوویی نوێکرانەوە',
           'expense' => $expense
       ]);
   }


   /**
    * Remove the specified resource from storage.
    */
   public function destroy($id)
   {
       // Delete expense
       Expense::findOrFail($id)->delete();

       return response()->json(['message' => 'خەرجییەکە بە سەرکەوتوویی سڕایەوە']);
   }
}
