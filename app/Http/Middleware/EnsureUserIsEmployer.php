<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsEmployer
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->role !== 2) {
            return response()->json([
                'message' => 'Only employers can perform this action.',
            ], 403);
        }

        return $next($request);
    }
}
