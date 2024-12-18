<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class EnsureUserIsAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        $route = Route::current();
        $middleware = $route->gatherMiddleware();

        if (in_array('auth:sanctum', $middleware)) {

            if (!Auth::guard('sanctum')->check()) {
            //    Log::info('Unauthenticated user');
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }

          //  Log::info('User is authenticated: ' . Auth::id());
        } else {
         Log::info('Route does not require authentication');
        }

        return $next($request);

    }
}
