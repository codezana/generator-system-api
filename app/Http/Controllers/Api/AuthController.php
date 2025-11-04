<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $users = Cache::remember("users_for_{$user->id}", 60, function () use ($user) {
            $query = User::query();

            if ($user->role === 'manager') {
                $query->where(function ($q) use ($user) {
                    $q->where('manager_id', $user->id)->orWhere('id', $user->id);
                });
            }

            return $query->select('id', 'name', 'role')->get();
        });

        return response()->json($users);
    }

    public function show($id)
    {
        $user = Auth::user();

        $query = User::query();

        if ($user->role === 'manager') {
            $query->where(function ($q) use ($user) {
                $q->where('manager_id', $user->id)->orWhere('id', $user->id);
            });
        }

        $targetUser = $query->findOrFail($id);

        return response()->json($targetUser);
    }

    public function login(Request $request)
    {
        $start = microtime(true); // Measure execution time

        $request->validate([
            'name' => 'required|string',
            'password' => 'required|string',
        ]);

        if (RateLimiter::tooManyAttempts($request->ip(), 10)) {
            return response()->json(['error' => 'Too many login attempts'], 429);
        }

        $user = User::select('id', 'name', 'password', 'role')
            ->where('name', $request->name)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($request->ip(), 60);
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        RateLimiter::clear($request->ip());

        $token = $user->createToken('GeneratorApp')->plainTextToken;

        $end = microtime(true);
        Log::info('Login Time: ' . ($end - $start)); // Log how long it took

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
            'token' => $token,
        ]);
    }


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'بەسەرکەوتووی چووەدەرەوە']);
    }

    public function store(Request $request)
    {
        $request->merge(['role' => strtolower(trim($request->role))]);

        if (Auth::user()->role === 'super_admin' && $request->role === 'admin') {
            return response()->json(['error' => 'ئەم کارە کاری تۆ نیە کاری بەڕێوبەرە'], 403);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'role' => 'required|in:super_admin,manager,admin',
        ]);

        if (Auth::user()->role === $validatedData['role']) {
            return response()->json(['error' => 'ناتوانیت بەکارهێنەرێک زیاد بکەیت کە هەمان ڕۆڵی خۆتی بێت'], 403);
        }

        $user = User::create([
            'name' => $validatedData['name'],
            'password' => Hash::make($validatedData['password']),
            'role' => $validatedData['role'],
            'manager_id' => Auth::user()->id,
        ]);

        return response()->json([
            'message' => 'بەکارهێنەر بە سەرکەوتوویی دروست کرا',
            'user' => $user,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string',
            'role' => 'in:super_admin,manager,admin'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => collect($validator->errors()->all())->first()], 422);
        }

        if (Auth::user()->role === 'super_admin' && $request->role === 'admin') {
            return response()->json(['error' => 'ئەم کارە کاری تۆ نیە کاری بەڕێوبەرە'], 403);
        }

        if (Auth::user()->role === $request->role) {
            return response()->json(['error' => 'ناتوانیت بەکارهێنەرێک نوێ بکەیتەوە کە هەمان ڕۆڵی خۆت بێت'], 403);
        }

        if ($request->has('role')) {
            if ($user->role === 'manager' && Auth::user()->role !== 'super_admin') {
                return response()->json(['error' => 'تەنها بەڕێوەبەری سەرەکی دەتوانێت ڕۆڵی بەڕێوەبەر بگۆڕێت'], 403);
            }

            if ($user->role === 'admin' && Auth::user()->role === 'manager' && $user->manager_id !== Auth::user()->id) {
                return response()->json(['error' => 'تەنها بەڕێوەبەری ئەم ئەدمینە دەتوانێت ڕۆڵەکەی بگۆڕێت'], 403);
            }
        }

        $user->fill($request->only(['name', 'role']));
        $user->save();

        return response()->json(['message' => 'بەکارهێنەر بە سەرکەوتوویی نوێکرایەوە']);
    }

    public function destroy($id)
    {
        if (Auth::user()->id == $id) {
            return response()->json(['error' => 'ناتوانی خۆت بسڕیتەوە'], 403);
        }

        $user = User::findOrFail($id);

        if (Auth::user()->role === 'super_admin' && $user->role === 'admin') {
            return response()->json(['error' => 'ئەم کارە کاری تۆ نیە کاری بەڕێوبەرە'], 403);
        }

        if ($user->role === 'manager' && User::where('manager_id', $user->id)->select('id')->exists()) {
            return response()->json(['error' => 'ناتوانیت بەڕێوەبەرێک بسڕیتەوە کە ئەدمینەکانی دیاری کردووە'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'بەکارهێنەر بە سەرکەوتوویی سڕایەوە']);
    }

    public function reset(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'password_change' => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => collect($validator->errors()->all())->first()], 422);
        }

        $user = User::findOrFail($id);
        $user->password = Hash::make($request->input('password_change', '12345678'));
        $user->save();

        return response()->json(['message' => 'پاسۆرد بە سەرکەوتوویی نوێکرایەوە']);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => collect($validator->errors()->all())->first()], 422);
        }

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'هەمان پاسۆردی پێشوو نییە'], 401);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'پاسۆرد بە سەرکەوتوویی نوێکرایەوە']);
    }
}
