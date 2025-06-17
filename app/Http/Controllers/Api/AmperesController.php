<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ampere;
use App\Models\Generator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AmperesController extends Controller
{  /**
    * Display a listing of the resource.
    */
   public function index()
   {
       $user = Auth::user();
   
       // Super Admin: Get all Ampere records
       if ($user->role === 'super_admin') {
           $ameres = Ampere::with('generator')->get();
       } else {
           // Get all generator IDs assigned to the current admin/manager
           $ameres = Ampere::whereHas('generator', function ($query) use ($user) {
               if ($user->role === 'manager') {
                   $query->where('manager_id', $user->id);
               } elseif ($user->role === 'admin') {
                   $query->where('admin_id', $user->id);
               }
           })->with('generator')->get();
       }
   
       // If no records are found, return an empty array (not an error)
       if ($ameres->isEmpty()) {
           return response()->json([]);
       }
   
       // Format response
       $details = $ameres->map(function ($ameres) {
           return [
               'id' => $ameres->id,
               'name_generator'=>$ameres->generator->name,
               'date' => $ameres->date,
               'ampere' => $ameres->generator->ampere ?? 'N/A',
               'total_hours' => $ameres->total_hours,
               'hourly_price' => $ameres->hourly_price,
               'final' => $ameres->final,
               'total' => $ameres->total,
               'paid' => $ameres->paid,
               'status' => $ameres->status
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
       $validator = Validator::make($request->all(), [
           'date' => 'required|date',
           'total_hours' => 'required|numeric',
           'hourly_price' => 'required|numeric',
           'final' => 'required|numeric',
           'total' => 'required|numeric',
           'paid' => 'required|numeric'
       ]);

       if ($validator->fails()) {
           return response()->json([
               'error' => collect($validator->errors()->all())->first()
           ], 422);
       }

       // Get validated data
       $validatedData = $validator->validated();

       // Get authenticated user's generator
       $generator = Generator::where('admin_id', Auth::user()->id)->first();

       // Check if the generator exists
       if (!$generator) {
           return response()->json([
               'error' => 'هیچ مۆلیدەیەک بۆ ئەم ئەدمینە نەدۆزراوەتەوە'
           ]);
       }

       // Assign the generator_id to the validated data
       $idgenerator = $generator->id;

       // Create the Ampere
       $ameres = Ampere::create([
           'generator_id' => $idgenerator,
           'date' => $validatedData['date'],
           'total_hours' => $validatedData['total_hours'],
           'hourly_price' => $validatedData['hourly_price'],
           'final' => $validatedData['final'],
           'total' => $validatedData['total'],
           'paid' => $validatedData['paid'],
           'status' => (float)$validatedData['total'] === (float)$validatedData['paid'] ? 'paid' : 'loan',

       ]);

       // Load relationships
       $ameres->load('generator');

       return response()->json([
           'message' => 'ئەمپێری مۆلیدە بە سەرکەوتوویی دروستکرا',
           'expense' => $ameres
       ], 201);
   }


   /**
    * Display the specified resource.
    */
   public function show($id)
   {
       $user = Auth::user();
   
       // Super Admin: Can access any Ampere record
       if ($user->role === 'super_admin') {
           $ampere = Ampere::with('generator')->findOrFail($id);
       } else {
           // Get generator IDs assigned to the current admin/manager
           $generators = Generator::where(function ($query) use ($user) {
               if ($user->role === 'manager') {
                   $query->where('manager_id', $user->id);
               } elseif ($user->role === 'admin') {
                   $query->where('admin_id', $user->id);
               }
           })->pluck('id');
   
           // If no generator is found, return error
           if ($generators->isEmpty()) {
               return response()->json([
                   'error' => 'هیچ مۆلیدەیەک بۆ ئەم بەکارهێنەرە نەدۆزراوەتەوە'
               ], 404);
           }
   
           // Find the requested Ampere record only if it belongs to one of the user's generators
           $ampere = Ampere::whereIn('generator_id', $generators)
               ->with('generator')
               ->findOrFail($id);
       }
   
       // Format response
       return response()->json([
           'id' => $ampere->id,
           'name_generator' => $ampere->generator->name,
           'ampere' => $ampere->generator->ampere ?? 'N/A',
           'date' => $ampere->date,
           'total_hours' => $ampere->total_hours,
           'hourly_price' => $ampere->hourly_price,
           'final' => $ampere->final,
           'total' => $ampere->total,
           'paid' => $ampere->paid,
           'status' => $ampere->status
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
           'date' => 'date',
           'total_hours' => 'numeric',
           'hourly_price' => 'numeric',
           'final' => 'numeric',
           'total' => 'numeric',
           'paid' => 'numeric'
       ]));

       if ($validated->fails()) {
           return response()->json([
               'error' => collect($validated->errors()->all())->first()
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

       $validated = $validated->validated();
       // Find the Ampere
       $ameres = Ampere::findOrFail($id);

       // Update only provided fields
       $ameres->update([
           'generator_id' => $idgenerator ?? $ameres->generator_id,
           'date' => $validated['date'] ?? $ameres->date,
           'total_hours' => $validated['total_hours'] ?? $ameres->total_hours,
           'hourly_price' => $validated['hourly_price'] ?? $ameres->hourly_price,
           'final' => $validated['final'] ?? $ameres->final,
           'total' => $validated['total']  ?? $ameres->total,
           'paid' => $validated['paid'] ?? $ameres->paid,
           'status' => (float)$validated['total'] === (float)$validated['paid'] ? 'paid' : 'loan',
       ]);

       // Load relationships
       $ameres->load(['generator']);

       return response()->json([
           'message' => 'ئەمپێری مۆلیدە بە سەرکەوتوویی نوێکرایەوە',
           'expense' => $ameres
       ]);
   }


   /**
    * Remove the specified resource from storage.
    */
   public function destroy($id)
   {
       // Delete Ampere
       Ampere::findOrFail($id)->delete();

       return response()->json(['message' => 'ئەمپێری مۆلیدە بە سەرکەوتوویی سڕایەوە']);
   }
}
