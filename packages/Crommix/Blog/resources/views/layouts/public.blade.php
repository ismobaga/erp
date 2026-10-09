<!DOCTYPE html>
<html class="scroll-smooth" lang="fr">

@php
    $blogCompany    = $blogCompany ?? currentCompany();
    $companyName    = $blogCompany?->name ?: config('app.name', 'CROMMIX');
    $companyLogoUrl = filled($blogCompany?->logo_path)
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($blogCompany->logo_path)
        : asset('images/cm-logo.svg');
    $activeCompany  = $blogCompany;
    // Inline @section values are already HTML-escaped; decode them so the
    // {{ }} output below escapes exactly once.
    $sectionText    = fn (string $name): string => trim(html_entity_decode($__env->yieldContent($name), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $pageTitle      = $sectionText('title') ?: $companyName . ' — Blog';
    $pageDescription = $sectionText('meta_description') ?: 'Articles et actualités.';
    $ogImage        = $sectionText('og_image') ?: $companyLogoUrl;
    $canonicalUrl   = $sectionText('canonical') ?: url()->current();
@endphp

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="alternate" type="application/rss+xml" title="{{ $companyName }} — Blog" href="{{ route('blog.feed') }}">

    {{-- Open Graph / social cards --}}
    <meta property="og:site_name" content="{{ $companyName }}">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:type" content="{{ $sectionText('og_type') ?: 'website' }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    @stack('meta')

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:500,600,700,600i|inter:400,500,600,700&display=swap" rel="stylesheet" />
    <meta name="theme-color" content="#fbf8f3">
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body class="bg-sand-50 font-sans text-ink-900 antialiased selection:bg-terra-200 selection:text-ink-950">
    @include('partials.site.header', ['siteCompany' => $blogCompany])

    <main id="contenu">
        @yield('content')
    </main>

    @include('partials.site.footer', ['siteCompany' => $blogCompany])

    @stack('scripts')
</body>
</html>
