<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Generator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GeneratorsController extends Controller
{
   
    /**
     * Display a listing of the resource.
     */

     public function index()
     {
         $user = Auth::user();
     
         if ($user->role === 'super_admin') {
             $generators = Generator::with('admin', 'manager')->get();
         } elseif ($user->role === 'manager') {
             $generators = Generator::where('manager_id', $user->id)->with('admin', 'manager')->get();
         } elseif ($user->role === 'admin') {
             $generators = Generator::where('admin_id', $user->id)->with('admin', 'manager')->get();
         } else {
             return response()->json([
                'error' => ' بە بێ مۆڵەت دەستڕاگەیشتن ڕێگەپێنەدراوە'
            ], 403);
         }
     
         return response()->json($generators);
     }
     

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        // check only role manager can create generator
        if (Auth::user()->role != 'manager') {
            return response()->json(['error' => 'بێ مۆڵەت : تۆ بەڕێوبەر نیت'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'admin_id' => 'required|exists:users,id',
            'location' => 'required|string|max:100',
            'ampere' => 'required|numeric|between:0,100000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => collect($validator->errors()->all())->first()
            ], 422);
        }

        // Check if the admin already has a generator assigned
        $existingGenerator = Generator::where('admin_id', $request->input('admin_id'))->first();
        if ($existingGenerator) {
            return response()->json([
                'error' => 'ئەم بەڕێوەبەرە پێشتر مۆلیدەیەکی هەیە'
            ], 422);
        }

        try {
            $generator = Generator::create([
                'name' => $request->input('name'),
                'admin_id' => $request->input('admin_id'),
                'manager_id' => Auth::id(),
                'location' => $request->input('location'),
                'ampere' => $request->input('ampere'),
            ]);

            return response()->json([
                'message' => 'مۆلیدە بە سەرکەوتوویی زیاد کرا',
                'data' => $generator,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'لە کاتی زیادکردنی مۆلیدەدا هەڵەیەک ڕوویدا',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */

     public function show($id)
     {
         $user = Auth::user();
     
         if ($user->role === 'super_admin') {
             $generator = Generator::with('admin', 'manager')->findOrFail($id);
         } elseif ($user->role === 'manager') {
             $generator = Generator::where('id', $id)
                 ->where('manager_id', $user->id)
                 ->with('admin', 'manager')
                 ->firstOrFail();
         } elseif ($user->role === 'admin') {
             $generator = Generator::where('id', $id)
                 ->where('admin_id', $user->id)
                 ->with('admin', 'manager')
                 ->firstOrFail();
         } else {
             return response()->json([
                 'error' => ' بە بێ مۆڵەت دەستڕاگەیشتن ڕێگەپێنەدراوە'
             ], 403);
         }
     
         return response()->json($generator);
     }
     

    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, string $id)
    {

        // check only role manager can update generator
        if (Auth::user()->role != 'manager') {
            return response()->json(['error' => 'بێ مۆڵەت : تۆ بەڕێوبەر نیت'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:100',
            'admin_id' => 'exists:users,id',
            'location' => 'string|max:100',
            'ampere' => 'numeric|between:0,100000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => collect($validator->errors()->all())->first()
            ], 422);
        }

        try {
            $generator = Generator::findOrFail($id);

            $generator->name = $request->input('name');
            $generator->admin_id = $request->input('admin_id');
            $generator->manager_id = Auth::id();
            $generator->location = $request->input('location');
            $generator->ampere = $request->input('ampere');
            $generator->save();

            return response()->json([
                'message' => 'مۆلیدە بە سەرکەوتوویی نوێکرایەوە',
                'data' => $generator,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'لە کاتی نوێکردنەوەی مۆلیدەدا هەڵەیەک ڕوویدا',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(string $id)
    {

        // check only role manager can delete generator
        if (Auth::user()->role != 'manager') {
            return response()->json(['error' => 'بێ مۆڵەت : تۆ بەڕێوبەر نیت'], 401);
        }

        try {
            $generator = Generator::findOrFail($id);
            $generator->delete();

            return response()->json([
                'message' => 'مۆلیدە بە سەرکەوتوویی سڕایەوە'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'لە کاتی سڕینەوەی مۆلیدەدا هەڵەیەک ڕوویدا',
                'data' => $e->getMessage(),
            ], 500);
        }
    }
}
