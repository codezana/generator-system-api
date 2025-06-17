<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneratorExpense;
use App\Models\Generator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class GenExpensesController extends Controller
{
      /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
    
        // Super Admin: Get all expenses with required relationships
        if ($user->role === 'super_admin') {
            $genexpense = GeneratorExpense::with(['types', 'generator.admin', 'generator.manager'])->get();
        } else {
            // Get all generators for the manager or admin
            $generators = Generator::where(function ($query) use ($user) {
                if ($user->role === 'manager') {
                    $query->where('manager_id', $user->id);
                } elseif ($user->role === 'admin') {
                    $query->where('admin_id', $user->id);
                }
            })->pluck('id'); // Get all generator IDs
    
            if ($generators->isEmpty()) {
                return response()->json([
                    'error' => 'هیچ مۆلیدەیەک بۆ ئەم بەکارهێنەرە نەدۆزراوەتەوە'
                ], 404);
            }
    
            // Fetch all Generator Expenses linked to those generators
            $genexpense = GeneratorExpense::whereIn('generator_id', $generators)
                ->with(['types', 'generator.admin', 'generator.manager'])
                ->get();
        }
    
        // Format response
        $details = $genexpense->map(function ($genexpense) {
            return [
                'id' => $genexpense->id,
                'name' => $genexpense->generator->name,
                'boss' => $genexpense->generator->admin->name ?? 'N/A',
                'manager' => $genexpense->generator->manager->name ?? 'N/A',
                'type' => $genexpense->types->name,
                'which' => $genexpense->which,
                'total' => $genexpense->total,
                'paid' => $genexpense->paid,
                'date' => $genexpense->date,
                'status' => $genexpense->status,
            ];
        });
    
        return response()->json($details);
    }
    

    /**
     * Store a newly created resource in storage.
     */

     public function store(Request $request)
     {
         // Validate request
         $validator = Validator::make($request->all(), [
             'type_id' => 'required|exists:types,id',
             'which' => 'nullable|string',
             'total' => 'required|numeric',
             'paid' => 'nullable|numeric',
             'date' => 'required|date',
            
         ]);
     
         if ($validator->fails()) {
             return response()->json([
                 'error' => collect($validator->errors()->all())->first()
             ], 422);
         }
     
         // Get authenticated user's generator
         $generator = Generator::where('admin_id', Auth::user()->id)->first();
     
         // Check if the generator exists
         if (!$generator) {
             return response()->json([
                 'error' => 'هیچ مۆلیدەیەک بۆ ئەم ئەدمینە نەدۆزراوەتەوە'
             ], 404);
         }
     
         $idgenerator = $generator->id;
     
         // Get validated data and add generator_id
         $validatedData = $validator->validated();
         $validatedData['generator_id'] = $idgenerator;
     
         // Create the Generator expense
         $genexpense = GeneratorExpense::create([
             'generator_id' => $idgenerator,
             'type_id' => $validatedData['type_id'],
             'which' => $validatedData['which'],
             'total' => $validatedData['total'],
             'paid' => $validatedData['paid'],
             'date' => $validatedData['date'],
             'status' => (float)$validatedData['total'] === (float)$validatedData['paid'] ? 'paid' : 'loan',
         ]);
     
         // Load relationships
         $genexpense->load(['types', 'generator']);
     
         return response()->json([
             'message' => 'خەرجی مۆلیدە بە سەرکەوتوویی دروستکرا',
             'expense' => $genexpense
         ], 201);
     }
     

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = Auth::user();
    
        // Super Admin can directly access
        if ($user->role === 'super_admin') {
            $genexpense = GeneratorExpense::with('types', 'generator.admin', 'generator.manager')->findOrFail($id);
        } else {
            // Ensure the user is either a manager or admin of the generator
            $generator = Generator::where(function ($query) use ($user) {
                if ($user->role === 'manager') {
                    $query->where('manager_id', $user->id);
                } elseif ($user->role === 'admin') {
                    $query->where('admin_id', $user->id);
                }
            })->first();
    
            if (!$generator) {
                return response()->json([
                    'error' => 'هیچ مۆلیدەیەک بۆ ئەم بەکارهێنەرە نەدۆزراوەتەوە'
                ], 404);
            }
    
            // Fetch Generator Expense linked to this generator
            $genexpense = GeneratorExpense::where('generator_id', $generator->id)
                ->with('types', 'generator.admin', 'generator.manager')
                ->findOrFail($id);
        }
    
        // Return formatted response
        return response()->json([
            'id' => $genexpense->id,
            'name' => $genexpense->generator->name,
            'boss' => $genexpense->generator->admin->name ?? 'N/A',
            'manager' => $genexpense->generator->manager->name ?? 'N/A',
            'type' => $genexpense->types->name,
            'which' => $genexpense->which,
            'total' => $genexpense->total,
            'paid' => $genexpense->paid,
            'date' => $genexpense->date,
            'status' => $genexpense->status,
        ]);
    }
    

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate input
        $validated = Validator::make($request->all(), ([
            'generator_id' => 'exists:generators,id',
            'type_id' => 'exists:types,id',
            'which' => 'nullable|string',
            'total' => 'nullable|numeric',
            'paid' => 'nullable|numeric',
            'date' => 'nullable|date',
           
        ]));
    
        if ( $validated->fails() ) {
            return response()->json( [
                'error' => collect( $validated->errors()->all() )->first()
            ], 422 );
        }

        $validated = $validated->validated();

        // Get authenticated user's generator
        $generator = Generator::where('admin_id', Auth::user()->id)->first();

        // Check if the generator exists
        if (!$generator) {
            return response()->json([
                'error' => 'هیچ مۆلیدەیەک بۆ ئەم ئەدمینە نەدۆزراوەتەوە'
            ], 404);
        }

        $idgenerator = $generator->id;
        // Find the Generator expense
        $genexpense = GeneratorExpense::findOrFail($id);
    
        // Update only provided fields
        $genexpense->update([
            'generator_id' => $idgenerator ?? $genexpense->generator_id,
            'type_id' => $validated['type_id'] ?? $genexpense->type_id,
            'which' => $validated['which'] ?? $genexpense->which,
            'total' => $validated['total'] ?? $genexpense->total,
            'paid' => $validated['paid'] ?? $genexpense->paid,
            'date' => $validated['date'] ?? $genexpense->date,
            'status' => (float)$validated['total'] === (float)$validated['paid'] ? 'paid' : 'loan' ?? $genexpense->status,

        ]);
    
        // Load relationships
        $genexpense->load(['types', 'generator']);
    
        return response()->json([
            'message' => 'خەرجی مۆلیدە بە سەرکەوتوویی نوێکرایەوە',
            'expense' => $genexpense
        ]);
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Delete Generator expense
        GeneratorExpense::findOrFail($id)->delete();

        return response()->json(['message' => 'خەرجی مۆلیدە بە سەرکەوتوویی سڕایەوە']);
    }
}
