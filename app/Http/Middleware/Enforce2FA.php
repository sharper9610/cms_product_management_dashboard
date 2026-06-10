<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Enforce2FA
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (auth()->check()) {
            $user = auth()->user();

            // If user hasn't enabled 2FA, redirect to setup
            if (!$user->google2fa_enabled) {
                return redirect()->route('2fa.setup')
                    ->with('warning', 'You must enable Two-Factor Authentication to access the system.');
            }
        }

        return $next($request);
    }
}
