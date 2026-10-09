<!DOCTYPE html>
<html class="scroll-smooth" lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', ($companyName ?? 'CROMMIX MALI S.A.') . ' — Site officiel')</title>
    <meta name="description" content="@yield('meta_description', 'Découvrez nos solutions et services.')">
    <meta name="theme-color" content="#fbf8f3">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:500,600,700,600i|inter:400,500,600,700&display=swap" rel="stylesheet" />

    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="{{ $companyName ?? 'CROMMIX' }}" />
    <link rel="manifest" href="/site.webmanifest" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body class="bg-sand-50 font-sans text-ink-900 antialiased selection:bg-terra-200 selection:text-ink-950">
    @include('partials.site.header', ['siteCompany' => $company ?? null])

    <main id="contenu">
        @yield('content')
    </main>

    @include('partials.site.footer', ['siteCompany' => $company ?? null])

    @stack('scripts')
</body>

</html>
