<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ApiAuthentication {

    public function handle($request, Closure $next) {

        if (!Auth::check()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $sessionKey = $request->session()->get('session_key');

        session(['session_key' => $sessionKey]);

        Log::info('ApiAuthentication->handle', [
            $sessionKey,
            $request->ip(),
            $request->method(),
            $request->path(),
        ]);

        return $next($request);
    }

}