<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PortifolioController;
use App\Http\Controllers\EmpreendimentoController;
use App\Http\Controllers\ViabilidadeController;



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

    
    Route::get('web/current-user', [UserController::class, 'current']);
    Route::get('web/menus', [UserController::class, 'getMenus']);


  
    Route::get('/web/dashboard', [PortifolioController::class, 'getDashboard']);
    Route::get('/web/portfolio-view', [PortifolioController::class, 'getPortfolioView']);
    Route::get('/web/portfolio', [PortifolioController::class, 'getPortfolio']);

    Route::delete('/web/empreendimento/{id}', [EmpreendimentoController::class, 'deleteEmpreendimento']);
    Route::post('/web/empreendimento', [EmpreendimentoController::class, 'saveEmpreendimento']);

    Route::get('/web/viabilidades/{empreendimentoId}', [ViabilidadeController::class, 'getViabilidades']);
    Route::post('/web/viabilidade', [ViabilidadeController::class, 'saveViabilidade']);
    Route::patch('/web/viabilidade/{id}/ativar', [ViabilidadeController::class, 'ativarViabilidade']);
    Route::delete('/web/viabilidade/{id}', [ViabilidadeController::class, 'deleteViabilidade']);

    

    //Catch-all
    Route::get('{any}', function () {
        return view('app');
    })->where('any', '^(?!api).*$');
});
