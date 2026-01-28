<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsFreelancer
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->role !== 3) {
            return response()->json([
                'message' => 'Only freelancers can perform this action.',
            ], 403);
        }

        return $next($request);
    }
}
