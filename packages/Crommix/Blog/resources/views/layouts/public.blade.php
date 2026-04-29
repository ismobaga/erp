<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Crommix Mali - Blog')</title>
    <meta name="description" content="@yield('meta_description', 'Crommix Mali - Innovation numérique pour l’Afrique - contenu public')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Newsreader:opsz,wght@6..72,400;500&display=swap"
        rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Newsreader', serif;
            background: #fbf9f8;
            color: #1b1c1c;
        }

        .lumina-label {
            font-family: 'Manrope', sans-serif;
            font-size: 11px;
            letter-spacing: .08em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .lumina-heading {
            font-family: 'Manrope', sans-serif;
        }

        .lumina-shell {
            max-width: 1120px;
            margin: 0 auto;
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

<body class="bg-[#fbf9f8] text-[#1b1c1c] antialiased">
    <header class="sticky top-0 z-50 border-b border-stone-200 bg-[#fbf9f8]/95 backdrop-blur-md">
        <nav class="lumina-shell flex items-center justify-between px-6 py-5 lg:px-8">
            <a href="{{ route('company.presentation') }}"
                class="lumina-heading text-2xl font-black tracking-tight text-[#1b4332] uppercase">{{ $companyName }}</a>

            <div class="hidden items-center gap-8 md:flex">
                <a href="{{ route('company.presentation') }}"
                    class="font-manrope text-sm font-medium text-stone-500 transition hover:text-[#1b4332]">Accueil</a>
                <a href="{{ route('blog.index') }}"
                    class="font-manrope border-b-2 border-[#1b4332] pb-1 text-sm font-semibold text-[#1b4332]">Blog</a>
                <a href="{{ route('company.presentation') }}#services"
                    class="font-manrope text-sm font-medium text-stone-500 transition hover:text-[#1b4332]">Services</a>
                <a href="{{ route('company.presentation') }}#contact"
                    class="font-manrope text-sm font-medium text-stone-500 transition hover:text-[#1b4332]">Contact</a>
            </div>

            <a href="{{ route('company.presentation') }}#contact"
                class="font-manrope rounded bg-[#1b4332] px-5 py-2.5 text-xs tracking-[0.08em] uppercase font-semibold text-white transition hover:opacity-90">
                Commencer
            </a>
        </nav>
    </header>

    @yield('content')

    <footer class="border-t border-stone-200 bg-stone-50 pb-10 pt-20">
        <div class="lumina-shell flex flex-col items-center justify-between gap-8 px-6 md:flex-row lg:px-8">
            <div class="lumina-heading text-xl font-black text-[#1b4332] uppercase">{{ $companyName }}</div>
            <div
                class="flex flex-wrap justify-center gap-6 font-manrope text-xs font-semibold uppercase tracking-widest text-stone-500">
                <a href="{{ route('company.presentation') }}" class="transition hover:text-[#1b4332]">Accueil</a>
                <a href="{{ route('blog.index') }}" class="transition hover:text-[#1b4332]">Blog</a>
                <a href="{{ route('company.presentation') }}#services"
                    class="transition hover:text-[#1b4332]">Services</a>
                <a href="{{ route('company.presentation') }}#contact"
                    class="transition hover:text-[#1b4332]">Contact</a>
            </div>
            <div class="font-manrope text-[10px] uppercase tracking-wider text-stone-400">
                © {{ now()->year }} {{ $companyName }}. Innovation numérique pour l’Afrique.
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>

</html>
