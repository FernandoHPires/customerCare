<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class NoAuthentication {

    public function handle($request, Closure $next) {
        
        if(is_null($request->session()->get('session_key'))) {
            $request->session()->put('session_key',Session::getId());
        }

        Log::info('NoAuthentication->handle', [
            Session::getId(),
            $request->ip(),
            $request->method(),
            $request->path(),
            json_encode($request->all())
        ]);

        return $next($request);
    }

}