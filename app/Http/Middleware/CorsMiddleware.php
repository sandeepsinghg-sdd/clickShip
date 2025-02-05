<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Add CORS headers
        // $response->headers->set('Access-Control-Allow-Origin', 'https://7f41-103-149-154-135.ngrok-free.app'); // Your ngrok URL
        // $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
        // $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        return $response;
    }
}
