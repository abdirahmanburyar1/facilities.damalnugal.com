<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasFacility
{
    /**
     * Facility-only app: user must have a valid (existing and active) facility.
     * If user has no facility_id, or facility was removed/deactivated, log them out immediately.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = $request->user();
        $user->refresh();
        $user->load('facility');

        // No facility assigned
        if (!$user->facility_id) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('error', 'Your account is not linked to a facility. Please contact an administrator.');
        }

        // Facility was deleted or deactivated
        if (!$user->facility || !$user->facility->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('error', 'Your facility has been removed or deactivated. You have been logged out.');
        }

        return $next($request);
    }
}
