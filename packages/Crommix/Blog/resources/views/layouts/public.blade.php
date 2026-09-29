<!DOCTYPE html>
<html class="scroll-smooth" lang="fr">

@php
    $blogCompany    = $blogCompany ?? currentCompany();
    $companyName    = $blogCompany?->name ?: config('app.name', 'CROMMIX');
    $companyLogoUrl = filled($blogCompany?->logo_path)
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($blogCompany->logo_path)
        : (file_exists(public_path('images/cm-logo.svg')) ? asset('images/cm-logo.svg') : null);
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
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>

<body class="bg-[#f8f9ff] text-[#0b1c30] antialiased">

    <header class="sticky top-0 z-50 border-b border-[#c4c6cf]/20 bg-[#f8f9ff]/95 backdrop-blur-xl">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
            <a href="{{ route('company.presentation') }}" class="flex items-center">
                @if($companyLogoUrl)
                    <img src="{{ $companyLogoUrl }}" alt="{{ $companyName }}" class="h-9 w-auto object-contain">
                @else
                    <span class="text-xl font-black tracking-tight text-[#002045] uppercase">{{ $companyName }}</span>
                @endif
            </a>

            {{-- Desktop nav --}}
            @php
                $isLabs = request()->routeIs('blog.category') && request()->route('slug') === \Crommix\Blog\Support\Labs::SLUG;
                $labsUrl = \Crommix\Blog\Support\Labs::url($blogCompany);
                $navLinks = collect([
                    ['url' => route('company.presentation'), 'label' => 'Accueil',   'active' => request()->routeIs('company.presentation')],
                    ['url' => route('company.about'),        'label' => 'À propos',  'active' => request()->routeIs('company.about')],
                    ['url' => route('company.services'),     'label' => 'Services',  'active' => request()->routeIs('company.services')],
                    ['url' => route('company.solutions'),    'label' => 'Solutions', 'active' => request()->routeIs('company.solutions')],
                    ['url' => route('blog.index'),           'label' => 'Blog',      'active' => request()->routeIs('blog.*') && ! $isLabs],
                    $labsUrl ? ['url' => $labsUrl,          'label' => 'Labs',      'active' => $isLabs] : null,
                    ['url' => route('company.contact'),      'label' => 'Contact',   'active' => request()->routeIs('company.contact')],
                ])->filter()->all();
            @endphp
            <div class="hidden items-center gap-1 md:flex">
                @foreach($navLinks as $link)
                    @php $active = $link['active']; @endphp
                    <a href="{{ $link['url'] }}"
                        class="relative px-3 py-2 text-sm font-medium transition-colors rounded-md
                               {{ $active ? 'text-[#002045] font-semibold' : 'text-[#43474e] hover:text-[#002045] hover:bg-[#eff4ff]' }}">
                        {{ $link['label'] }}
                        @if($active)
                            <span class="absolute bottom-0 left-3 right-3 h-0.5 rounded-full bg-[#002045]"></span>
                        @endif
                    </a>
                @endforeach
                <a href="/admin/login"
                    class="ml-4 rounded-lg border border-[#c4c6cf]/60 px-3 py-1.5 text-xs font-semibold uppercase tracking-widest text-[#002045] transition hover:bg-[#eff4ff]">Connexion</a>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('company.contact') }}"
                    class="hidden rounded-lg bg-[#002045] px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 md:inline-flex">
                    Nous contacter
                </a>

                {{-- Mobile hamburger --}}
                <button id="mobile-menu-btn" type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-[#c4c6cf]/40 text-[#002045] transition hover:bg-[#eff4ff] md:hidden"
                    aria-label="Menu" aria-expanded="false">
                    <svg id="icon-open"  class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg id="icon-close" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </nav>

        {{-- Mobile menu --}}
        <div id="mobile-menu" class="hidden border-t border-[#c4c6cf]/20 bg-[#f8f9ff] md:hidden">
            <div class="flex flex-col px-6 py-4 gap-1">
                @foreach($navLinks as $link)
                    @php $active = $link['active']; @endphp
                    <a href="{{ $link['url'] }}"
                        class="rounded-lg px-4 py-3 text-sm font-medium transition
                               {{ $active ? 'bg-[#eff4ff] text-[#002045] font-semibold' : 'text-[#43474e] hover:bg-[#eff4ff] hover:text-[#002045]' }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach
                <div class="mt-3 flex flex-col gap-2 border-t border-[#c4c6cf]/20 pt-3">
                    <a href="{{ route('company.contact') }}"
                        class="rounded-lg bg-[#002045] px-4 py-3 text-center text-sm font-semibold text-white">
                        Nous contacter
                    </a>
                    <a href="/admin/login"
                        class="rounded-lg border border-[#c4c6cf]/60 px-4 py-3 text-center text-xs font-semibold uppercase tracking-widest text-[#002045]">
                        Connexion
                    </a>
                </div>
            </div>
        </div>
    </header>

    <script nonce="{{ csp_nonce() }}">
        (function () {
            const btn = document.getElementById('mobile-menu-btn');
            const menu = document.getElementById('mobile-menu');
            const iconOpen = document.getElementById('icon-open');
            const iconClose = document.getElementById('icon-close');
            btn?.addEventListener('click', function () {
                const expanded = btn.getAttribute('aria-expanded') === 'true';
                btn.setAttribute('aria-expanded', String(!expanded));
                menu?.classList.toggle('hidden');
                iconOpen?.classList.toggle('hidden');
                iconClose?.classList.toggle('hidden');
            });
        })();
    </script>

    <main>
        @yield('content')
    </main>

    <footer class="border-t border-[#c4c6cf]/10 bg-[#eff4ff] pb-10 pt-20">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-8 px-6 md:flex-row lg:px-8">
            <div>
                @if($companyLogoUrl)
                    <img src="{{ $companyLogoUrl }}" alt="{{ $companyName }}" class="h-8 w-auto object-contain">
                @else
                    <span class="text-xl font-black text-[#002045] uppercase">{{ $companyName }}</span>
                @endif
            </div>
            <div class="flex flex-wrap justify-center gap-6 text-xs font-semibold uppercase tracking-widest text-[#43474e]">
                <a href="{{ route('company.confidentialite') }}" class="transition hover:text-[#005048]">Confidentialité</a>
                <a href="{{ route('company.conditions') }}"     class="transition hover:text-[#005048]">Conditions</a>
                <a href="{{ route('company.cookies') }}"        class="transition hover:text-[#005048]">Cookies</a>
                @if(company_feature_enabled('blog', $activeCompany))
                <a href="{{ route('blog.index') }}"             class="transition hover:text-[#005048]">Blog</a>
                @endif
                <a href="/admin/login"                          class="transition hover:text-[#005048]">Portail ERP</a>
            </div>
            <div class="flex flex-col items-center gap-1 text-center text-[10px] uppercase tracking-wider text-[#43474e]/70">
                <span>© {{ now()->year }} {{ $companyName }}. Pour votre transformation numérique.</span>
                <span>En partenariat avec <a href="https://crommix.com/" target="_blank" rel="noopener noreferrer"
                    class="underline transition hover:text-[#005048]">Crommix</a> — Burkina Faso</span>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
