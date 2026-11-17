<?php

namespace App\Http\Middleware;

use App\Services\FreePlan;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * An agency beyond the free plan can look at the portal (any GET) but every action it tries —
 * holding seats, changing names, releasing seats, sending a payment — is refused.
 */
class EnsureAgencyWithinFreePlan
{
    public function handle(Request $request, Closure $next): Response
    {
        $agency = $request->user()?->agency;

        if ($agency && ! $request->isMethodSafe() && ! FreePlan::covers($agency)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => FreePlan::lockedMessage()]);

            return back();
        }

        return $next($request);
    }
}
