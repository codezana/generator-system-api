<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class Role
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // If user is not logged in → 401 Unauthorized
        if (!Auth::check()) {
            return response()->json(['error' => 'پێویستە چوونەژوورەوە بکەیت'], 401);
        }
    
        // Get the user's role
        $userRole = Auth::user()->role;
    
        // Check if user's role is in the allowed roles
        if (!in_array($userRole, $roles)) {
            Log::error("User role '{$userRole}' not in allowed roles: " . implode(',', $roles));
            return response()->json(['error' => 'تۆ مۆڵەتت نییە بۆ ئەم کردەیە'], 403);
        }
    
        return $next($request);
    }
}
