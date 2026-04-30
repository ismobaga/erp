<!DOCTYPE html>
<html class="scroll-smooth" lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', ($company?->company_name ?: config('app.name')) . ' — Présentation')</title>
    <meta name="description" content="@yield('meta_description', 'Découvrez nos solutions et services.')">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-[#f8f9ff] text-[#0b1c30] antialiased">
    <!-- Header / Navigation -->
    <header class="sticky top-0 z-50 border-b border-[#c4c6cf]/20 bg-[#f8f9ff]/90 backdrop-blur-xl">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:px-8">
            <a href="{{ route('company.presentation') }}"
                class="text-2xl font-black tracking-tight text-[#002045] uppercase">
                {{ $companyName }}
            </a>

            <div class="hidden items-center gap-8 md:flex">
                @yield('nav_links')
            </div>

            <a href="#contact"
                class="rounded-lg bg-[#002045] px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                Demander une démo
            </a>
        </nav>
    </header>

    <!-- Main Content -->
    <main id="top">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-[#c4c6cf]/10 bg-[#eff4ff] pb-10 pt-20">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-8 px-6 md:flex-row lg:px-8">
            <div class="text-xl font-black text-[#002045] uppercase">{{ $companyName }}</div>
            <div
                class="flex flex-wrap justify-center gap-6 text-xs font-semibold uppercase tracking-widest text-[#43474e]">
                <a href="{{ route('company.confidentialite') }}"
                    class="transition hover:text-[#005048]">Confidentialité</a>
                <a href="{{ route('company.conditions') }}" class="transition hover:text-[#005048]">Conditions</a>
                <a href="{{ route('company.cookies') }}" class="transition hover:text-[#005048]">Cookies</a>
                <a href="{{ route('company.bureaux') }}" class="transition hover:text-[#005048]">Bureaux</a>
            </div>
            <div
                class="flex flex-col items-center gap-1 text-center text-[10px] uppercase tracking-wider text-[#43474e]/70">
                <span>© {{ now()->year }} {{ $companyName }}. Pour votre transformation numérique.</span>
                <span>En partenariat avec <a href="https://crommix.com/" target="_blank" rel="noopener noreferrer"
                        class="underline transition hover:text-[#005048]">Crommix</a> — Burkina Faso</span>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>

</html>