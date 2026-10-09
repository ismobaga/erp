{{-- Public site footer, shared by the marketing pages and the blog. Expects (optional) $siteCompany. --}}
@php
    $siteCompany ??= null;
    $blogOn = $siteCompany !== null && company_feature_enabled('blog', $siteCompany);
    $footerName = $siteCompany?->name ?: 'CROMMIX MALI S.A.';
    $footerEmail = $siteCompany?->email ?: 'contact@crommix.com';
    $footerPhone = $siteCompany?->phone;
@endphp

<footer class="bg-bogolan-dark text-sand-200">
    <div class="weave"></div>
    <div class="site-container grid gap-12 py-16 md:grid-cols-12">
        <div class="md:col-span-5">
            <img src="{{ asset('images/cm-logo-light.svg') }}" alt="CROMMIX MALI S.A." class="h-9 w-auto" width="138" height="36">
            <p class="mt-6 max-w-sm text-sm leading-relaxed text-sand-200/80">
                Logiciels, données et conseil IT conçus à Bamako pour les organisations d’Afrique de l’Ouest.
            </p>
            <div class="mt-6 space-y-2 text-sm">
                <a href="mailto:{{ $footerEmail }}" class="flex items-center gap-2.5 text-sand-100 transition hover:text-terra-400">
                    <x-site.icon name="mail" class="h-4 w-4 text-terra-400" /> {{ $footerEmail }}
                </a>
                @if ($footerPhone)
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $footerPhone) }}" class="flex items-center gap-2.5 text-sand-100 transition hover:text-terra-400">
                        <x-site.icon name="phone" class="h-4 w-4 text-terra-400" /> {{ $footerPhone }}
                    </a>
                @endif
            </div>
        </div>

        <nav class="md:col-span-2" aria-label="Entreprise">
            <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-sand-400">Entreprise</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="{{ route('company.about') }}" class="transition hover:text-terra-400">À propos</a></li>
                <li><a href="{{ route('company.bureaux') }}" class="transition hover:text-terra-400">Bureaux</a></li>
                <li><a href="{{ route('company.contact') }}" class="transition hover:text-terra-400">Contact</a></li>
                @if ($blogOn)
                    <li><a href="{{ route('blog.index') }}" class="transition hover:text-terra-400">Blog</a></li>
                @endif
            </ul>
        </nav>

        <nav class="md:col-span-3" aria-label="Offre">
            <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-sand-400">Offre</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="{{ route('company.services') }}" class="transition hover:text-terra-400">Services</a></li>
                <li><a href="{{ route('company.solutions') }}" class="transition hover:text-terra-400">Solutions & produits</a></li>
                <li><a href="{{ route('dms.presentation') }}" class="transition hover:text-terra-400">DMS — pharmacies</a></li>
                <li><a href="/admin/login" class="transition hover:text-terra-400">Portail ERP</a></li>
            </ul>
        </nav>

        <nav class="md:col-span-2" aria-label="Légal">
            <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-sand-400">Légal</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="{{ route('company.confidentialite') }}" class="transition hover:text-terra-400">Confidentialité</a></li>
                <li><a href="{{ route('company.conditions') }}" class="transition hover:text-terra-400">Conditions</a></li>
                <li><a href="{{ route('company.cookies') }}" class="transition hover:text-terra-400">Cookies</a></li>
            </ul>
        </nav>
    </div>

    <div class="border-t border-sand-50/10">
        <div class="site-container flex flex-col gap-2 py-6 text-xs text-sand-200/60 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ now()->year }} {{ $footerName }}. Tous droits réservés.</p>
            <p>En partenariat avec <a href="https://crommix.com/" target="_blank" rel="noopener noreferrer" class="underline underline-offset-2 transition hover:text-terra-400">Crommix</a> — Burkina Faso</p>
        </div>
    </div>
</footer>
