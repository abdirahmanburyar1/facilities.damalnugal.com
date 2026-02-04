<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Models\TrustedDevice;
use Illuminate\Support\Facades\Auth;

class TwoFactorAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If user is authenticated but hasn't completed 2FA
        if (auth()->check() && !Session::has('two_factor_authenticated')) {
            // Check for trusted device cookie
            $trustedToken = $request->cookie('trusted_device_token');
            $user = Auth::user();
            
            // Debug logging
            logger()->info('2FA Debug', [
                'user_id' => $user->id,
                'has_trusted_token' => !empty($trustedToken),
                'trusted_token' => $trustedToken ? substr($trustedToken, 0, 10) . '...' : null,
                'session_has_2fa' => Session::has('two_factor_authenticated'),
                'current_url' => url()->current(),
            ]);
            
            if ($trustedToken && $user) {
                $trustedDevice = TrustedDevice::where('user_id', $user->id)
                    ->where('device_token', $trustedToken)
                    ->first();
                
                logger()->info('Trusted device check', [
                    'found_device' => $trustedDevice ? 'yes' : 'no',
                    'device_id' => $trustedDevice ? $trustedDevice->id : null,
                ]);
                
                if ($trustedDevice) {
                    Session::put('two_factor_authenticated', true);
                    $request->session()->save(); // Ensure session is saved!
                    $trustedDevice->update(['last_used_at' => now()]);
                    
                    logger()->info('2FA bypassed via trusted device');
                    return $next($request);
                }
            }
            
            // Store intended URL in session
            Session::put('url.intended', url()->current());
            
            logger()->info('Redirecting to 2FA verification');
            // Redirect to 2FA verification page
            return redirect()->route('two-factor.show');
        }

        return $next($request);
    }
}