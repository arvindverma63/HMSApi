<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        // Check if user is authenticated and has one of the allowed roles
        if (!$user || !in_array($user->role, $roles)) {
            return response()->json(['error' => 'Unauthorized. Insufficient role permissions.'], 403);
        }

        return $next($request);
    }
}
