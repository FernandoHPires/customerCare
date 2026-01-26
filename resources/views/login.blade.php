<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
        <meta name="description" content="Amur Financial Group">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!--<link rel="apple-touch-icon" sizes="180x180" href="images/favicon/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="images/favicon/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="images/favicon/favicon-16x16.png">
        <link rel="manifest" href="images/favicon/site.webmanifest">-->

        <title>Amur Financial Group</title>

        <!-- Styles -->
        <link href="{{ asset('css/app.css?') }}{{ uniqid() }}" rel="stylesheet">
    </head>

    <body>
        <div id="app"></div>

        <!-- Scripts -->
        <script src="{{ asset('js/app.js?') }}{{ uniqid() }}"></script>
    </body>
</html>
