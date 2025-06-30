<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header("X-API-TOKEN");

        if($token !== config("app.api_token")) {
            return response()->json(["status" => "error", "message" => "Unauthorized"], 401);
        }

        return $next($request);
    }
}
