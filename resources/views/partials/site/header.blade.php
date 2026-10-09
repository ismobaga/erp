{{--
    Public site header, shared by the marketing pages and the blog.
    Expects (optional) $siteCompany: the company whose site is served.
--}}
@php
    $siteCompany ??= null;
    $blogOn = $siteCompany !== null && company_feature_enabled('blog', $siteCompany);
    $labsUrl = $blogOn && class_exists(\Crommix\Blog\Support\Labs::class) ? \Crommix\Blog\Support\Labs::url($siteCompany) : null;
    $onLabs = request()->routeIs('blog.category') && request()->route('slug') === 'labs';

    $navLinks = array_values(array_filter([
        ['url' => route('company.about'), 'label' => 'À propos', 'active' => request()->routeIs('company.about')],
        ['url' => route('company.services'), 'label' => 'Services', 'active' => request()->routeIs('company.services')],
        ['url' => route('company.solutions'), 'label' => 'Solutions', 'active' => request()->routeIs('company.solutions', 'dms.presentation')],
        $blogOn ? ['url' => route('blog.index'), 'label' => 'Blog', 'active' => request()->routeIs('blog.*') && ! $onLabs] : null,
        $labsUrl ? ['url' => $labsUrl, 'label' => 'Labs', 'active' => $onLabs] : null,
        ['url' => route('company.bureaux'), 'label' => 'Bureaux', 'active' => request()->routeIs('company.bureaux')],
    ]));
@endphp

<a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-full focus:bg-ink-900 focus:px-4 focus:py-2 focus:text-sand-50">
    Aller au contenu
</a>

<header class="sticky top-0 z-50 border-b border-sand-200/80 bg-sand-50/90 backdrop-blur-lg">
    <nav class="site-container flex h-[4.5rem] items-center justify-between gap-6" aria-label="Navigation principale">
        <a href="{{ route('company.presentation') }}" class="shrink-0" aria-label="Accueil — {{ $siteCompany?->name ?? 'CROMMIX MALI S.A.' }}">
            <img src="{{ asset('images/cm-logo.svg') }}" alt="CROMMIX MALI S.A." class="h-10 w-auto" width="153" height="40">
        </a>

        <div class="hidden items-center gap-1 lg:flex">
            @foreach ($navLinks as $link)
                <a href="{{ $link['url'] }}" @if ($link['active']) aria-current="page" @endif
                    class="relative rounded-full px-3.5 py-2 text-[0.9375rem] font-medium transition
                           {{ $link['active'] ? 'text-ink-900' : 'text-ink-600 hover:text-ink-900' }}">
                    {{ $link['label'] }}
                    @if ($link['active'])
                        <span class="absolute inset-x-3.5 -bottom-0.5 h-0.5 rounded-full bg-terra-600"></span>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="flex items-center gap-2">
            <a href="/admin/login" class="hidden px-3 py-2 text-sm font-medium text-ink-600 transition hover:text-ink-900 lg:inline-flex">
                Connexion
            </a>
            <a href="{{ route('company.contact') }}" class="btn btn-primary btn-sm hidden sm:inline-flex">
                Nous contacter
            </a>
            <button id="site-menu-btn" type="button" aria-controls="site-menu" aria-expanded="false" aria-label="Ouvrir le menu"
                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-sand-300 text-ink-900 transition hover:bg-sand-100 lg:hidden">
                <x-site.icon name="menu" class="h-5 w-5" data-icon="open" />
                <x-site.icon name="x-mark" class="hidden h-5 w-5" data-icon="close" />
            </button>
        </div>
    </nav>

    <div id="site-menu" class="hidden border-t border-sand-200 bg-sand-50 lg:hidden">
        <div class="site-container flex flex-col gap-1 py-4">
            <a href="{{ route('company.presentation') }}" class="rounded-xl px-4 py-3 text-base font-medium {{ request()->routeIs('company.presentation') ? 'bg-sand-100 text-ink-900' : 'text-ink-700 hover:bg-sand-100' }}">Accueil</a>
            @foreach ($navLinks as $link)
                <a href="{{ $link['url'] }}" @if ($link['active']) aria-current="page" @endif
                    class="rounded-xl px-4 py-3 text-base font-medium {{ $link['active'] ? 'bg-sand-100 text-ink-900' : 'text-ink-700 hover:bg-sand-100' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
            <div class="mt-3 grid grid-cols-2 gap-2 border-t border-sand-200 pt-4">
                <a href="{{ route('company.contact') }}" class="btn btn-primary">Nous contacter</a>
                <a href="/admin/login" class="btn btn-outline">Connexion</a>
            </div>
        </div>
    </div>
</header>

<script nonce="{{ csp_nonce() }}">
    (function () {
        const btn = document.getElementById('site-menu-btn');
        const menu = document.getElementById('site-menu');
        btn?.addEventListener('click', function () {
            const open = btn.getAttribute('aria-expanded') !== 'true';
            btn.setAttribute('aria-expanded', String(open));
            btn.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
            menu?.classList.toggle('hidden', !open);
            btn.querySelector('[data-icon=open]')?.classList.toggle('hidden', open);
            btn.querySelector('[data-icon=close]')?.classList.toggle('hidden', !open);
        });
    })();
</script>
