<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ResponseTimeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        $response = $next($request);

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Add response time header
        $response->headers->set('X-Response-Time', round($responseTime, 2) . 'ms');

        // Log slow responses (over 200ms)
        if ($responseTime > 200) {
            Log::warning('Slow API response detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'response_time' => round($responseTime, 2) . 'ms',
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }
}
