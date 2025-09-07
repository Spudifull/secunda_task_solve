<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $want = config('app.api_key', env('API_KEY'));
        $have = $request->header('X-API-Key');
        if (!$want || !$have || !hash_equals($want, $have)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}

