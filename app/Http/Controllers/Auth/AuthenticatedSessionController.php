<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Check for trusted device
        $trustedToken = $request->cookie('trusted_device_token');
        $user = auth()->user();
        
        if ($trustedToken && $user) {
            $trustedDevice = \App\Models\TrustedDevice::where('user_id', $user->id)
                ->where('device_token', $trustedToken)
                ->first();
                
            if ($trustedDevice) {
                // Update last used timestamp
                $trustedDevice->update(['last_used_at' => now()]);
                
                // Mark as 2FA authenticated
                session(['two_factor_authenticated' => true]);
                
                // Redirect to dashboard
                return redirect()->intended(route('dashboard'));
            }
        }

        // If no trusted device found, redirect to 2FA
        return redirect()->route('two-factor.show');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
