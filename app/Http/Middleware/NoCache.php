<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoCache
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Para respuestas "normales" de Laravel (JsonResponse, Response, etc.)
        if (method_exists($response, 'header')) {
            return $response
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        // Para BinaryFileResponse / StreamedResponse (descargas)
        if ($response instanceof Response) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
