<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;


Route::group(['middleware' => 'noAuthentication'], function () {

    Route::get('login', function () {
        return view('login');
    });

    //Login
    Route::post('api/login', [LoginController::class, 'login']);

});



Route::group(['middleware' => 'webAuthentication'], function () {
    //Logout
    Route::get('web/logout', [LoginController::class, 'logout']);
    Route::get('web/check-session', [LoginController::class, 'checkSession']);

    //Users
    Route::get('web/current-user', [UserController::class, 'current']);
    Route::get('web/menus', [UserController::class, 'getMenus']);
    Route::get('/web/users/brokers', [UserController::class, 'getBrokers']);
    Route::get('/web/users/group-users', [UserController::class, 'getUsersByGroups']);
    Route::get('/web/users/current/groups', [UserController::class, 'getCurrentUserGroups']);
    Route::get('/web/users/external-brokers', [UserController::class, 'getExternalBrokers']);
    Route::get('/web/users/accounting', [UserController::class, 'getAccountingUsers']);
    Route::get('/web/users/funding', [UserController::class, 'getFundingUsers']);
    Route::get('/web/users/support', [UserController::class, 'getSupportUsers']);

    

    //Catch-all
    Route::get('{any}', function () {
        return view('app');
    })->where('any', '^(?!api).*$');
});
