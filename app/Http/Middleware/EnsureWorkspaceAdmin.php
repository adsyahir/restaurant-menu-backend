<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict a route to members who hold the `admin` role in their current
 * workspace. Waiters and kitchen staff are members but must not manage the
 * menu, tables, staff, or billing.
 */
class EnsureWorkspaceAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $isAdmin = $user !== null
            && $user->workspaces()
                ->wherePivot('is_active', true)
                ->whereKey($user->current_workspace_id)
                ->wherePivot('role', 'admin')
                ->exists();

        if (! $isAdmin) {
            abort(403, 'This action requires workspace admin access.');
        }

        return $next($request);
    }
}
