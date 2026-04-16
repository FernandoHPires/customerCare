<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PortifolioController;
use App\Http\Controllers\EmpreendimentoController;
use App\Http\Controllers\ViabilidadeController;
use App\Http\Controllers\SimulacaoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\AlterarSenhaController;
use App\Http\Controllers\PermissaoController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\ClientViewController;



Route::group(['middleware' => 'noAuthentication'], function () {

    Route::get('login', function () {
        return view('login');
    });

    //Login
    Route::post('api/login', [LoginController::class, 'login']);

    // 2FA (parte do fluxo de login — sem autenticação completa ainda)
    Route::post('api/two-factor/verify', [TwoFactorController::class, 'verify']);
    Route::post('api/two-factor/resend', [TwoFactorController::class, 'resend']);

});



Route::group(['middleware' => 'webAuthentication'], function () {
    
    //Logout
    Route::get('web/logout', [LoginController::class, 'logout']);
    Route::get('web/check-session', [LoginController::class, 'checkSession']);

    
    Route::get('web/current-user', [UserController::class, 'current']);
    Route::post('web/client-view/{companyId}', [ClientViewController::class, 'set']);
    Route::delete('web/client-view', [ClientViewController::class, 'clear']);
    Route::get('web/menus', [UserController::class, 'getMenus']);

    Route::post('/web/alterar-senha', [AlterarSenhaController::class, 'alterarSenha']);

    Route::get('/web/perfis', [PerfilController::class, 'getPerfis']);
    Route::post('/web/perfil', [PerfilController::class, 'savePerfil']);
    Route::delete('/web/perfil/{id}', [PerfilController::class, 'deletePerfil']);

    Route::get('/web/permissoes', [PermissaoController::class, 'getPermissoes']);
    Route::get('/web/permissao/{perfilId}', [PermissaoController::class, 'getMenusPerfil']);
    Route::post('/web/permissao/{perfilId}', [PermissaoController::class, 'savePermissao']);
    Route::get('/web/usuarios', [UserController::class, 'getUsers']);
    Route::post('/web/usuario', [UserController::class, 'saveUser']);
    Route::delete('/web/usuario/{id}', [UserController::class, 'deleteUser']);


  
    Route::get('/web/dashboard', [PortifolioController::class, 'getDashboard']);
    Route::get('/web/portfolio-view', [PortifolioController::class, 'getPortfolioView']);
    Route::get('/web/portfolio', [PortifolioController::class, 'getPortfolio']);

    Route::delete('/web/empreendimento/{id}', [EmpreendimentoController::class, 'deleteEmpreendimento']);
    Route::post('/web/empreendimento', [EmpreendimentoController::class, 'saveEmpreendimento']);

    Route::get('/web/viabilidades/{empreendimentoId}', [ViabilidadeController::class, 'getViabilidades']);
    Route::post('/web/viabilidade', [ViabilidadeController::class, 'saveViabilidade']);
    Route::patch('/web/viabilidade/{id}/ativar', [ViabilidadeController::class, 'ativarViabilidade']);
    Route::delete('/web/viabilidade/{id}', [ViabilidadeController::class, 'deleteViabilidade']);

    Route::get('/web/simulacao', [SimulacaoController::class, 'getSimulacao']);
    Route::post('/web/simulacao', [SimulacaoController::class, 'saveSimulacao']);

    Route::get('/web/clientes', [ClienteController::class, 'getClientes']);
    Route::post('/web/cliente', [ClienteController::class, 'saveCliente']);
    Route::delete('/web/cliente/{id}', [ClienteController::class, 'deleteCliente']);

    

    //Catch-all
    Route::get('{any}', function () {
        return view('app');
    })->where('any', '^(?!api).*$');
});
