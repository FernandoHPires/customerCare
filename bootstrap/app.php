<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__.'/../routes/web.php',
        api:      __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health:   '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // ── Trust proxies (Nginx / load-balancer) ─────────────────────────
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                   | Request::HEADER_X_FORWARDED_HOST
                   | Request::HEADER_X_FORWARDED_PORT
                   | Request::HEADER_X_FORWARDED_PROTO
                   | Request::HEADER_X_FORWARDED_AWS_ELB
        );

        // ── Middleware global (todas as requisições) ──────────────────────
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // ── API group: sessão habilitada (ApiAuthentication usa session()) ─
        $middleware->appendToGroup('api', [
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        // ── Aliases de middleware (usados nas rotas) ──────────────────────
        $middleware->alias([
            'accessRight'       => \App\Http\Middleware\AccessRight::class,
            'noAuthentication'  => \App\Http\Middleware\NoAuthentication::class,
            'webAuthentication' => \App\Http\Middleware\WebAuthentication::class,
            'ssoAuthentication' => \App\Http\Middleware\SsoAuthentication::class,
            'apiAuthentication' => \App\Http\Middleware\ApiAuthentication::class,
        ]);

        // ── CSRF: excluir rotas herdadas (limpeza de exceções antigas) ────
        $middleware->validateCsrfTokens(except: [
            // Adicione aqui rotas que precisem ignorar CSRF (ex: webhooks)
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {

        // Nunca exibe senha nos flashes de validação
        $exceptions->dontFlash(['password', 'password_confirmation', 'current_password']);

        // Envia email com o erro em produção — detalhes completos estão no log do servidor
        $exceptions->report(function (\Throwable $e) {
            if (env('APP_ENV') === 'production') {
                $sessionKey = session('session_key');

                \App\AUni\Utilities\Utils::sendEmail(
                    ['fhpires9@gmail.com'],
                    'UNI Error - ' . class_basename($e),
                    implode("\n", [
                        'Sessão : ' . $sessionKey,
                        'Erro   : ' . $e->getMessage(),
                        'Arquivo: ' . basename($e->getFile()) . ':' . $e->getLine(),
                        '',
                        'Consulte o log do servidor para o stack trace completo.',
                    ])
                );
            }
        });

        // Retorna JSON padronizado para erros de validação
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                    'errors'  => $e->errors(),
                ], 200);
            }
        });

    })->create();
