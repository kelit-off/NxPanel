<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowApi
{   

    protected $internalIps = [
        '127.0.0.1',      // localhost
        '::1',             // IPv6 localhost
    ];
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        $host = $request->header('host'); // récupère le Host du header HTTP

        // Vérifier si IP interne
        foreach ($this->internalIps as $internalIp) {
            if ($ip === $internalIp) {
                return $next($request);
            }
        }

        // Vérifier si domaine autorisé
        if ($host === 'api.nxhost.fr') {
            return $next($request);
        }

        // Sinon refuser
        abort(403, 'Accès interdit.');
    }
}
