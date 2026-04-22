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
use App\Http\Controllers\InviteController;



Route::group(['middleware' => 'noAuthentication'], function () {

    Route::get('login', function () {
        return view('login');
    });

    //Login — máx. 10 tentativas por minuto por IP
    Route::post('api/login', [LoginController::class, 'login'])->middleware('throttle:10,1');

    // 2FA (parte do fluxo de login — sem autenticação completa ainda)
    Route::post('api/two-factor/verify', [TwoFactorController::class, 'verify'])->middleware('throttle:10,1');
    Route::post('api/two-factor/resend', [TwoFactorController::class, 'resend'])->middleware('throttle:3,1');

    // Convite — link enviado por e-mail (acesso sem autenticação prévia)
    Route::get('convite/{token}', [InviteController::class, 'accept'])->middleware('throttle:20,1');

});



Route::group(['middleware' => 'webAuthentication'], function () {
    
    //Logout
    Route::get('web/logout', [LoginController::class, 'logout']);
    Route::get('web/check-session', [LoginController::class, 'checkSession']);

    
    Route::get('web/current-user', [UserController::class, 'current']);
    Route::post('web/client-view/{companyId}', [ClientViewController::class, 'set']);
    Route::delete('web/client-view', [ClientViewController::class, 'clear']);
    Route::get('web/menus', [UserController::class, 'getMenus']);

    Route::post('/web/alterar-senha', [AlterarSenhaController::class, 'alterarSenha'])->middleware('throttle:5,1');

    // ── Perfis — somente quem tem acesso ao menu /perfis ─────────────────
    Route::get('/web/perfis', [PerfilController::class, 'getPerfis'])->middleware('accessRight:/perfis');
    Route::post('/web/perfil', [PerfilController::class, 'savePerfil'])->middleware('accessRight:/perfis');
    Route::delete('/web/perfil/{id}', [PerfilController::class, 'deletePerfil'])->middleware('accessRight:/perfis');

    // ── Permissões — somente quem tem acesso ao menu /permissoes ─────────
    Route::get('/web/permissoes', [PermissaoController::class, 'getPermissoes'])->middleware('accessRight:/permissoes');
    Route::get('/web/permissao/{perfilId}', [PermissaoController::class, 'getMenusPerfil'])->middleware('accessRight:/permissoes');
    Route::post('/web/permissao/{perfilId}', [PermissaoController::class, 'savePermissao'])->middleware('accessRight:/permissoes');

    // ── Usuários — somente quem tem acesso ao menu /usuarios ─────────────
    Route::get('/web/usuarios', [UserController::class, 'getUsers'])->middleware('accessRight:/usuarios');
    Route::post('/web/usuario', [UserController::class, 'saveUser'])->middleware('accessRight:/usuarios');
    Route::delete('/web/usuario/{id}', [UserController::class, 'deleteUser'])->middleware('accessRight:/usuarios');


  
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

    // ── Clientes — somente quem tem acesso ao menu /clientes ─────────────
    Route::get('/web/clientes', [ClienteController::class, 'getClientes'])->middleware('accessRight:/clientes');
    Route::post('/web/cliente', [ClienteController::class, 'saveCliente'])->middleware('accessRight:/clientes');
    Route::delete('/web/cliente/{id}', [ClienteController::class, 'deleteCliente'])->middleware('accessRight:/clientes');

    Route::post('/web/usuario/{userId}/enviar-convite', [InviteController::class, 'send'])->middleware('accessRight:/usuarios');

    

    //Catch-all
    Route::get('{any}', function () {
        return view('app');
    })->where('any', '^(?!api).*$');
});
