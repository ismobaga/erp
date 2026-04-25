<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Crommix Forge')</title>
    <meta name="description" content="@yield('meta_description', 'Crommix Forge - contenu public')">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>

    @stack('styles')
</head>

@php
    $company = \Illuminate\Support\Facades\Schema::hasTable('company_settings')
        ? \App\Models\CompanySetting::query()->first()
        : null;

    $companyName = $company?->company_name ?: 'CROMMIX';
@endphp

<body class="bg-[#f8f9ff] text-[#0b1c30] antialiased">
    <header class="sticky top-0 z-50 border-b border-[#c4c6cf]/20 bg-[#f8f9ff]/90 backdrop-blur-xl">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:px-8">
            <a href="{{ route('company.presentation') }}"
                class="text-2xl font-black tracking-tight text-[#002045] uppercase">{{ $companyName }}</a>

            <div class="hidden items-center gap-8 md:flex">
                <a href="{{ route('company.presentation') }}"
                    class="text-sm font-medium text-[#43474e] transition hover:text-[#002045]">Accueil</a>
                <a href="{{ route('blog.index') }}"
                    class="border-b-2 border-[#8df5e4] pb-1 text-sm font-medium text-[#002045]">Blog</a>
                <a href="{{ route('company.presentation') }}#services"
                    class="text-sm font-medium text-[#43474e] transition hover:text-[#002045]">Services</a>
                <a href="{{ route('company.presentation') }}#contact"
                    class="text-sm font-medium text-[#43474e] transition hover:text-[#002045]">Contact</a>
            </div>

            <a href="{{ route('company.presentation') }}#contact"
                class="rounded-lg bg-[#002045] px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                Commencer
            </a>
        </nav>
    </header>

    @yield('content')

    <footer class="border-t border-[#c4c6cf]/10 bg-[#eff4ff] pb-10 pt-20">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-8 px-6 md:flex-row lg:px-8">
            <div class="text-xl font-black text-[#002045] uppercase">{{ $companyName }}</div>
            <div
                class="flex flex-wrap justify-center gap-6 text-xs font-semibold uppercase tracking-widest text-[#43474e]">
                <a href="{{ route('company.presentation') }}" class="transition hover:text-[#005048]">Accueil</a>
                <a href="{{ route('blog.index') }}" class="transition hover:text-[#005048]">Blog</a>
                <a href="{{ route('company.presentation') }}#services"
                    class="transition hover:text-[#005048]">Services</a>
                <a href="{{ route('company.presentation') }}#contact"
                    class="transition hover:text-[#005048]">Contact</a>
            </div>
            <div class="text-[10px] uppercase tracking-wider text-[#43474e]/70">
                © {{ now()->year }} {{ $companyName }}. Précision architecturale au service des entreprises.
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>

</html>