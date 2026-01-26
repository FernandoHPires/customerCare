<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class ApiAuthentication {

    public function handle($request, Closure $next) {

        $sessionKey = $request->session()->get('session_key');
        
        session(['session_key' => $sessionKey]);

        Log::info('ApiAuthentication->handle', [
            $sessionKey,
            $request->ip(),
            $request->method(),
            $request->path(),
            json_encode($request->all())
        ]);

        return $next($request);
    }

}