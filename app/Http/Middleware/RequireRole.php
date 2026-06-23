<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['detail' => 'Authentication credentials were not provided.'], 401);
        }

        if (in_array('admin', $roles, true) && $user->isAdmin()) {
            return $next($request);
        }

        if (in_array('staff', $roles, true) && ($user->isAdmin() || $user->isStaffRole())) {
            return $next($request);
        }

        if (in_array('customer', $roles, true) && $user->role === 'customer') {
            return $next($request);
        }

        return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
    }
}
