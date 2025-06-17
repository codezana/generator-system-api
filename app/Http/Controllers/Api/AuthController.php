<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller {
    //index method

    public function index() {
        // if auth user role is manager retrieve based on manager_id and include self
        if ( Auth::user()->role == 'manager' ) {
            return response()->json( User::where( 'manager_id', Auth::user()->id )
            ->orWhere( 'id', Auth::user()->id )
            ->get() );
        }

        return response()->json( User::all() );
    }

    //View user

    public function show( $id ) {
        // if auth user role is manager retrieve based on manager_id and include self
        if ( Auth::user()->role == 'manager' ) {
            $user = User::where( 'manager_id', Auth::user()->id )
            ->orWhere( 'id', Auth::user()->id )
            ->findOrFail( $id );
            return response()->json( $user );
        }

        $user = User::findOrFail( $id );
        return response()->json( $user );
    }

    //login

    public function login(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'password' => 'required',
        ]);
    
        // Check for rate limiting (optional)
        if (RateLimiter::tooManyAttempts($request->ip(), 10)) {
            return response()->json(['error' => 'هەوڵی چوونەژوورەوە زۆرە'], 429);
        }
    
        // Search for the user (optimized query)
        $user = User::where('name', $request->name)->first();
    
        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($request->ip(), 60); // Record failed attempt
            return response()->json(['error' => 'ئەم زانیاریانە لەگەڵ تۆمارەکانمان یەک ناگرنەوە'], 401);
        }
    
        // Successful login
        RateLimiter::clear($request->ip()); // Clear attempts
    
        $token = $user->createToken('GeneratorApp')->plainTextToken;
    
        return response()->json([
            'message' => 'چوونەژوورەوە سەرکەوتوو بوو',
            'user' => $user,
            'token' => $token,
        ]);
    }

    //logout

    public function logout( Request $request ) {
        // Invalidate the token
        $request->user()->currentAccessToken()->delete();
        return response()->json( [ 'message' => 'بەسەرکەوتووی چووەدەرەوە' ] );
    }
    //create user

    public function store(Request $request)
    {
        // Sanitize the role field
        $request->merge(['role' => strtolower(trim($request->role))]);
    
        // Super admin can't create admin
        if (Auth::user()->role == 'super_admin' && $request->role == 'admin') {
            return response()->json(['error' => 'ئەم کارە کاری تۆ نیە کاری بەڕێوبەرە'], 403);
        }
    
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'role' => 'required|in:super_admin,manager,admin'
        ]);
    
        if (Auth::user()->role == $validatedData['role']) {
            return response()->json(['error' => 'ناتوانیت بەکارهێنەرێک زیاد بکەیت کە هەمان ڕۆڵی خۆتی بێت'], 403);
        }
    
        $user = User::create([
            'name' => $validatedData['name'],
            'password' => Hash::make($validatedData['password']),
            'role' => $validatedData['role'],
            'manager_id' => Auth::user()->id
        ]);
    
        return response()->json([
            'message' => 'بەکارهێنەر بە سەرکەوتوویی دروست کرا',
            'user' => $user
        ]);
    }
    

    //update user

    public function update(Request $request, $id)
    {
        // Find the user to update
        $user = User::findOrFail($id);

        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'string',
            'role' => 'in:super_admin,manager,admin'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => collect($validator->errors()->all())->first()
            ], 422);
        }

        // Check if super_admin is trying to modify admin role
        if (Auth::user()->role == 'super_admin' && $request->role == 'admin') {
            return response()->json(['error' => 'ئەم کارە کاری تۆ نیە کاری بەڕێوبەرە'], 403);
        }

        // Check if trying to set same role as authenticated user
        if (Auth::user()->role == $request->role) {
            return response()->json(['error' => 'ناتوانیت بەکارهێنەرێک نوێ بکەیتەوە کە هەمان ڕۆڵی خۆت بێت'], 403);
        }

        // Check role update permissions
        if ($request->has('role')) {
            // Only super_admin can update manager's role
        if ( $user->role == 'manager' && Auth::user()->role != 'super_admin' ) {
            return response()->json( [ 'error' => 'تەنها بەڕێوەبەری سەرەکی دەتوانێت ڕۆڵی بەڕێوەبەر بگۆڕێت' ], 403 );
        }

        // Only manager can update their admin's role
            if ($user->role == 'admin') {
                if (Auth::user()->role == 'manager' && $user->manager_id != Auth::user()->id) {
                    return response()->json(['error' => 'تەنها بەڕێوەبەری ئەم ئەدمینە دەتوانێت ڕۆڵەکەی بگۆڕێت'], 403);
                }
            }
        }

        // Update user
        $user->name = $request->input('name', $user->name);
        if ($request->has('role')) {
            $user->role = $request->input('role');
        }
        $user->save();

        return response()->json(['message' => 'بەکارهێنەر بە سەرکەوتوویی نوێکرایەوە']);
    }
    
    //delete user

    public function destroy($id)
    {

        // if that user is user who loged cant delete
        if (Auth::user()->id == $id) {
            return response()->json(['error' => 'ناتوانی خۆت بسڕیتەوە'], 403);
        }

        $user = User::findOrFail($id);


        if (Auth::user()->role == 'super_admin' && $user->role == 'admin') {
            return response()->json(['error' => 'ئەم کارە کاری تۆ نیە کاری بەڕێوبەرە'], 403);
        }

        // Check if the user is a manager with assigned admins
        if ($user->role == 'manager' && User::where('manager_id', $user->id)->exists()) {
            return response()->json(['error' => 'ناتوانیت بەڕێوەبەرێک بسڕیتەوە کە ئەدمینەکانی دیاری کردووە'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'بەکارهێنەر بە سەرکەوتوویی سڕایەوە']);
    }


    //Reset Password

    public function reset(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'password_change' => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => collect($validator->errors()->all())->first()
            ], 422);
        }

        $user = User::findOrFail($id);
        if ($request->input('password_change')) {
            $user->password = Hash::make($request->input('password_change'));
        } else {
            $user->password = Hash::make('12345678');
        }
        $user->save();

        return response()->json(['message' => 'پاسۆرد بە سەرکەوتوویی نوێکرایەوە']);
    }


    // Change Password

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => collect($validator->errors()->all())->first()
            ]);
        }

        $id = Auth::user()->id;
        $user = User::findOrFail($id);
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return response()->json(['error' => 'هەمان پاسۆردی پێشوو نییە'], 401);
        }
        $user->password = Hash::make($request->input('new_password'));
        $user->save();
        return response()->json(['message' => 'پاسۆرد بە سەرکەوتوویی نوێکرایەوە' ] );
    }
}
