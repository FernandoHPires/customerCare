<?php

namespace App\Http\Middleware;

use Closure;
use App\Amur\BO\AccessRightBO;

class AccessRight {

    public function handle($request, Closure $next, $role) {
        
        if(AccessRightBO::check($role)) {
            return $next($request);
        }

        return response()->json(['status' => 'error', 'message' => 'not allowed'], 403);
    }

}