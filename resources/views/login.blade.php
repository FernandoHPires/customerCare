<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
        <meta name="description" content="Gestão de Negócios">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="icon" type="image/png" href="/images/Favicon_Prancheta-1-150x150.png">

        <title>Gestão de Negócios</title>

        <!-- Styles -->
        <link href="{{ asset('css/app.css?') }}{{ uniqid() }}" rel="stylesheet">

        <!-- Cloudflare Turnstile -->
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    </head>

    <body>
        <div id="app"></div>

        <!-- Scripts -->
        <script src="{{ asset('js/app.js?') }}{{ uniqid() }}"></script>

        @if(session('invite_error'))
        <script>
            sessionStorage.setItem('auth_alert', '{{ session('invite_error') }}');
        </script>
        @endif
    </body>
</html>
