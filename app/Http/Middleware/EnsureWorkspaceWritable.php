<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks mutating requests against a restaurant that is read-only because the
 * owner's subscription doesn't cover it (expired free trial, or beyond the
 * plan's restaurant limit after a downgrade). Read requests always pass.
 */
class EnsureWorkspaceWritable
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $user = $request->user();
        $workspace = $user ? Workspace::find($user->current_workspace_id) : null;

        if ($workspace !== null && $workspace->isLocked()) {
            abort(403, 'This restaurant is read-only. Upgrade your subscription to make changes.');
        }

        return $next($request);
    }
}
