<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireApiKey
{
    public function handle(Request $request, Closure $next, string $service = 'contpaqi'): Response
    {
        $provided = $request->header('X-API-KEY');
        $expected = config("services.$service.api_key");

        if (!$expected || !$provided || !hash_equals((string)$expected, (string)$provided)) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}