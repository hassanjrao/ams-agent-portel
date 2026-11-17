<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The agency side: AMS staff are sent to their own dashboard, and a login with no agency is refused.
 */
class EnsureUserIsAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isAdmin()) {
            return to_route('admin.dashboard');
        }
        abort_unless($user?->agency_id, 403, 'This login is not linked to an agency.');

        return $next($request);
    }
}
