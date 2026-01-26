<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class WebAuthentication {

    public function handle($request, Closure $next) {
        
        if(is_null($request->session()->get('session_key'))) {
            $request->session()->put('session_key',Session::getId());
        }

        Log::info('WebAuthentication->handle', [
            Session::getId(),
            $request->ip(),
            $request->method(),
            $request->path(), 
            Auth::user()->user_id ?? null,
            json_encode($request->all())
        ]);
        
        if(Auth::check()) {
            return $next($request);
        }

        return redirect('login');
    }

}