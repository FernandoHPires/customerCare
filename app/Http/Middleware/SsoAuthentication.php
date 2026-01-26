<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SsoAuthentication {

    public function handle($request, Closure $next) {
        
        if(is_null($request->session()->get('session_key'))) {
            $request->session()->put('session_key',Session::getId());
        }

        Log::info('SsoAuthentication->handle', [
            Session::getId(),
            $request->ip(),
            $request->method(),
            $request->path(),
            $request->session()->get('user_id'),
            json_encode($request->all())
        ]);
        
        if(Auth::check() || env('APP_ENV') != 'production') {
            $request->session()->put('redirect', '');
            return $next($request);
        }

        $request->session()->put('redirect', $request->path());
        return redirect(env('AMUR_PORTAL') . '/web/sso-auth?system=' . env('SYSTEM_APPLICATION_ID'));
    }

}