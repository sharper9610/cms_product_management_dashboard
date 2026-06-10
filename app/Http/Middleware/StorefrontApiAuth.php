<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class StorefrontApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedPassword = config('services.storefront.password');
        $providedPassword = $request->query('password');

        Log::channel('api_middleware')->info('Storefront API access attempt', [
            'ip'                     => $request->ip(),
            'route'                  => $request->path(),
            'provided_password_hash' => $providedPassword ? sha1($providedPassword) : null,
            'status'                 => $providedPassword === $expectedPassword ? 'success' : 'failed',
        ]);

        if (!$providedPassword || $providedPassword !== $expectedPassword) {
            return response()->json([
                'Response' => [
                    'Version'   => '1.0',
                    'ErrorCode' => '401',
                    'ErrorMsg'  => 'Unauthorized, Invalid Password',
                    'Value'     => [],
                ]
            ], 401);
        }

        return $next($request);
    }
}
