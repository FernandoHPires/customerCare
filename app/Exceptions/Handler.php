<?php

namespace App\Exceptions;

use App\Amur\Utilities\Utils;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var string[]
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var string[]
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            if(env('APP_ENV') == 'production') {

                $sessionKey = session('session_key');

                Utils::sendEmail(
                    ['fhpires9@gmail.com'],
                    env('APP_ENV') . ' - Strive Error',
                    $sessionKey . ' | ' . $e->getMessage() . ' | ' . $e->getTraceAsString()
                );
            }
        });
    }

    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json([
            'status' => 'error',
            'message' => $exception->getMessage(),
            'errors' => $exception->errors(),
        ], 200);
    }
}
