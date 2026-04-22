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
        ]);
        
        if(Auth::check()) {

            // Sessão única: verifica se o token da sessão atual bate com o registrado no banco
            $user = Auth::user();
            if ($user->session_token && $user->session_token !== Session::getId()) {
                Auth::logout();
                $request->session()->invalidate();

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Sua sessão foi encerrada pois houve um login em outro dispositivo.',
                    ], 401);
                }

                return redirect('login');
            }

            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => 'error', 'message' => 'Sua sessão expirou por inatividade. Faça login novamente.'], 401);
        }

        return redirect('login');
    }

}