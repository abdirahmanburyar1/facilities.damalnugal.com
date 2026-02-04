<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TrustedDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class TwoFactorController extends Controller
{
    /**
     * Show the 2FA verification form.
     */
    public function show()
    {
        // If user is not authenticated, redirect to login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // If user has already completed 2FA, redirect to dashboard
        if (Session::has('two_factor_authenticated')) {
            return redirect()->intended(route('dashboard'));
        }

        // Check if there's an existing code and if it has expired
        if (Session::has('two_factor_expires_at') && Session::get('two_factor_expires_at') < now()) {
            // Clear the expired code
            Session::forget('two_factor_code');
            Session::forget('two_factor_expires_at');
            
            // Log the user out and redirect to login
            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();
            
            return redirect()->route('login')->with('status', 'Your verification code has expired. Please log in again.');
        }

        // Generate a new code only if one doesn't exist
        if (!Session::has('two_factor_code')) {
            // Generate a 6-digit code
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store the code in the session
            Session::put('two_factor_code', $code);
            Session::put('two_factor_expires_at', now()->addMinutes(10));
            
            // Send the code to the user's email
            $user = Auth::user();
            $this->sendTwoFactorCode($user, $code);
        }
        
        // Return the 2FA verification view
        return Inertia::render('Auth/TwoFactor', [
            'status' => session('status'),
        ]);
    }

    /**
     * Verify the 2FA code.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        // Check if the code has expired
        if (!Session::has('two_factor_expires_at') || Session::get('two_factor_expires_at') < now()) {
            // Log the user out and redirect to login
            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();
            
            return redirect()->route('login')->with('status', 'Your verification code has expired. Please log in again.');
        }

        // Check if the code is correct
        if ($request->code !== Session::get('two_factor_code')) {
            return back()->withErrors([
                'code' => 'The verification code is incorrect.',
            ]);
        }

        // Mark the user as 2FA authenticated
        Session::put('two_factor_authenticated', true);
        
        // Remove the code from the session
        Session::forget('two_factor_code');
        Session::forget('two_factor_expires_at');

        // Handle trusted device
        $trustedToken = $request->cookie('trusted_device_token');
        $user = Auth::user();
        if (!$trustedToken || !TrustedDevice::where('user_id', $user->id)->where('device_token', $trustedToken)->exists()) {
            // Generate a new trusted device token
            $newToken = Str::random(64);
            TrustedDevice::create([
                'user_id' => $user->id,
                'device_token' => $newToken,
                'device_name' => $request->header('User-Agent'),
                'ip_address' => $request->ip(),
                'last_used_at' => now(),
            ]);
            // Set a long-lived cookie (e.g., 1 year)
            $cookie = cookie('trusted_device_token', $newToken, 60 * 24 * 365); // 1 year
            return redirect()->intended(route('dashboard'))->withCookie($cookie);
        }
        // Redirect to the intended URL or dashboard
        return redirect()->intended(route('dashboard'));
    }

    /**
     * Resend the 2FA code.
     */
    public function resend()
    {
        // Generate a new 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store the code in the session
        Session::put('two_factor_code', $code);
        Session::put('two_factor_expires_at', now()->addMinutes(10));
        
        // Send the code to the user's email
        $user = Auth::user();
        $this->sendTwoFactorCode($user, $code);
        
        return back()->with('status', 'A new verification code has been sent to your email.');
    }

    /**
     * Send the 2FA code to the user's email.
     */
    private function sendTwoFactorCode(User $user, string $code)
    {
        try {
            $data = [
                'user' => $user,
                'code' => $code
            ];

            Mail::send('emails.two-factor', $data, function($message) use ($user) {
                $message->to($user->email)
                        ->subject('Your Two-Factor Authentication Code');
            });
        } catch (\Exception $e) {
            // Log the error but don't fail the request
            logger()->error($e->getMessage());
        }
    }
}
