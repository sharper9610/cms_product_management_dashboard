<?php 

// app/Http/Middleware/VerifyWebhookSignature.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret    = config('services.webhook_secret');
        
        $signature = $request->header('X-Webhook-Signature');
        if (!$secret || !$signature) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (($secret !== $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}