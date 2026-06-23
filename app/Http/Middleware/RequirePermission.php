<?php

namespace App\Http\Middleware;

use App\Support\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['detail' => 'Authentication credentials were not provided.'], 401);
        }

        if (! PermissionService::hasPermission($user, $permission)) {
            return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
        }

        return $next($request);
    }
}
