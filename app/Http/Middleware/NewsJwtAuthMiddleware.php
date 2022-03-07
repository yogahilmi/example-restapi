<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

class NewsJwtAuthMiddleware
{
    public function handle($request, Closure $next, $guard = null)
    {
        $token = $request->header('Authorization');

        if(!$token) {
            // Unauthorized response if token not there
            return response()->json([
                'message' => 'missing or malformed jwt'
            ], 401);
        }
        try {
            JWT::$leeway = env('JWT_DURATION', 60); // $leeway in seconds
            JWT::decode($token, env('JWT_NEWS_SECRET'), ['HS256']);
        } catch(ExpiredException $e) {
            return response()->json([
                'message' => 'Provided token is expired.'
            ], 400);
        } catch(Exception $e) {
            return response()->json([
                'message' => 'An error while decoding token.'
            ], 400);
        }

        return $next($request);
    }
}
