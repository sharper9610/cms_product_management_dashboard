<?php

namespace App\Http\Middleware;

use App\Models\ApiUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DynamicApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp = $request->ip();
        $providedPassword = $request->query('password');

        Log::channel('api_middleware')->info('Dynamic API auth attempt', [
            'ip' => $clientIp,
            'route' => $request->path(),
            'provided_password_hash' => $providedPassword ? sha1($providedPassword) : null,
        ]);


        if (!$providedPassword) {
            Log::channel('api_middleware')->warning('Missing password', ['ip' => $clientIp, 'route' => $request->path()]);
            return $this->unauthorized("Missing password");
        }

        $access = ApiUser::where('ip', $clientIp)->first();

        if (!$access || !Hash::check($providedPassword, $access->password)) {
            Log::channel('api_middleware')->warning('Unauthorized access attempt', ['ip' => $clientIp, 'route' => $request->path()]);
            return $this->unauthorized("Unauthorized, Invalid IP or Password");
        }

        Log::channel('api_middleware')->info('API access granted', ['ip' => $clientIp, 'route' => $request->path()]);

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        return response()->json([
            'Response' => [
                'Version'   => '1.0',
                'ErrorCode' => '401',
                'ErrorMsg'  => $message,
                'Value'     => [],
            ]
        ], 401);
    }
}
