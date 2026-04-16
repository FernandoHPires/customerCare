<?php

namespace App\Http\Controllers;

use App\AUni\Bean\Logger;
use App\AUni\BO\TwoFactorBO;
use Illuminate\Http\Request;

class TwoFactorController extends Controller {

    public function verify(Request $request) {
        $bo       = new TwoFactorBO(new Logger());
        $result   = $bo->verify($request->input('code'), $request);

        $response          = new \stdClass();
        $response->status  = $result['status'];
        $response->message = $result['message'];
        $response->data    = null;

        return response()->json($response);
    }

    public function resend(Request $request) {
        $bo       = new TwoFactorBO(new Logger());
        $result   = $bo->resend($request);

        $response          = new \stdClass();
        $response->status  = $result['status'];
        $response->message = $result['message'];
        $response->data    = null;

        return response()->json($response);
    }
}
