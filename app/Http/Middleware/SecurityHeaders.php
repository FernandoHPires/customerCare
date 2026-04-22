<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders {

    public function handle(Request $request, Closure $next) {

        $response = $next($request);

        // Impede que a página seja carregada em iframes de outros domínios (clickjacking)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Impede que o browser "adivinhe" o tipo do conteúdo (MIME sniffing)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Ativa o filtro XSS embutido em navegadores mais antigos
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Não envia o referer completo para outros domínios
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Em produção, força HTTPS por 1 ano
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
