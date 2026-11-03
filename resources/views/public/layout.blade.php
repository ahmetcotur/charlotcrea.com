<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>@yield('title', 'Charlot Crea')</title>
<link rel="stylesheet" href="{{ asset('client-portal.css') }}">
</head>
<body>
<div class="portal-shell">
<header class="portal-top">@include('site.partials.client-brand',['brandUrl'=>'/','brandCaption'=>'Müşteri deneyimi'])</header>
<main class="portal-main">
@yield('content')
</main>
@include('site.partials.client-footer',['clientLocale'=>'tr'])
</div>
</body>
</html>
