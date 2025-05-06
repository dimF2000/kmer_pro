<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->type === 'admin') {
            return $next($request);
        }

        return response()->json([
            'message' => 'Accès non autorisé'
        ], 403);
    }
} 