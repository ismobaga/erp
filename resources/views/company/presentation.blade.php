@extends('layouts.public')

@section('title', 'CROMMIX MALI S.A. — Le numérique bâti pour l’Afrique de l’Ouest')

@section('meta_description', 'CROMMIX MALI S.A. — logiciels métier, ERP, données et conseil IT conçus à Bamako pour les organisations d’Afrique de l’Ouest.')

@section('content')
    {{-- ── Hero ─────────────────────────────────────────────────────────── --}}
    <section class="relative overflow-hidden">
        <div class="site-container grid items-center gap-14 py-16 lg:grid-cols-12 lg:py-24">
            <div class="lg:col-span-7">
                <p class="eyebrow">Bamako · Afrique de l’Ouest</p>
                <h1 class="display mt-6 text-5xl sm:text-6xl lg:text-7xl">
                    Le numérique, <em class="font-semibold text-terra-700">bâti</em> pour l’Afrique de l’Ouest.
                </h1>
                <p class="mt-7 max-w-xl text-lg leading-relaxed text-ink-600">
                    CROMMIX MALI S.A. accompagne les organisations africaines avec des solutions concrètes :
                    développement logiciel, ERP, données et conseil IT.
                </p>
                <div class="mt-10 flex flex-wrap gap-3">
                    <a href="{{ route('company.solutions') }}" class="btn btn-primary">
                        Découvrir nos solutions <x-site.icon name="arrow-right" class="h-4 w-4" />
                    </a>
                    <a href="{{ route('company.presentation', ['intent' => 'Demande démo DMS']) }}#contact" class="btn btn-outline">
                        Demander une démo
                    </a>
                </div>
                <ul class="mt-12 flex flex-wrap gap-x-8 gap-y-3 text-sm text-ink-600">
                    <li class="flex items-center gap-2"><x-site.icon name="check" class="h-4 w-4 text-kola-600" /> DMS disponible</li>
                    <li class="flex items-center gap-2"><x-site.icon name="check" class="h-4 w-4 text-kola-600" /> Équipe à Bamako</li>
                    <li class="flex items-center gap-2"><x-site.icon name="check" class="h-4 w-4 text-kola-600" /> Pensé pour une connectivité variable</li>
                </ul>
            </div>

            <div class="relative mx-auto w-full max-w-md lg:col-span-5 lg:max-w-none">
                <div class="absolute -right-6 top-10 bottom-0 left-10 arch bg-bogolan" aria-hidden="true"></div>
                <div class="arch relative aspect-[4/5] bg-ink-900 shadow-[0_40px_80px_-40px_rgb(46_38_32/0.6)]">
                    <img src="{{ asset('images/mali.png') }}" alt="Bamako au crépuscule, le pont et le fleuve Niger"
                        class="h-full w-full object-cover" width="768" height="960">
                </div>
                <div class="absolute -left-4 bottom-8 w-60 rounded-2xl border border-sand-200 bg-white/95 p-5 shadow-xl backdrop-blur sm:-left-10">
                    <span class="badge badge-available"><span class="h-1.5 w-1.5 rounded-full bg-kola-600"></span> Disponible</span>
                    <p class="display mt-3 text-2xl">DMS</p>
                    <p class="mt-1 text-sm text-ink-600">Logiciel de gestion officinale pour les pharmacies.</p>
                    <a href="{{ route('dms.presentation') }}" class="link-arrow mt-3 text-sm">Découvrir <x-site.icon name="arrow-right" class="h-3.5 w-3.5" /></a>
                </div>
            </div>
        </div>
    </section>

    <div class="weave"></div>

    {{-- ── Approche ─────────────────────────────────────────────────────── --}}
    <section class="bg-white py-24">
        <div class="site-container">
            <div class="grid gap-10 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <p class="eyebrow">Notre approche</p>
                    <h2 class="display mt-5 text-4xl">Des fondations numériques durables.</h2>
                    <p class="mt-5 text-ink-600">Nous ne construisons pas seulement des logiciels ; nous érigeons des structures numériques pérennes.</p>
                </div>
                <div class="grid gap-px overflow-hidden rounded-[1.5rem] border border-sand-200 bg-sand-200 sm:grid-cols-3 lg:col-span-8">
                    @foreach ([
                        ['01', 'shield', 'Intégrité', 'Une transparence absolue dans chaque transaction et chaque ligne de code livrée.'],
                        ['02', 'light-bulb', 'Innovation', 'Nous repoussons les limites technologiques pour résoudre les défis locaux les plus concrets.'],
                        ['03', 'bolt', 'Efficacité', 'Des processus optimisés pour une croissance rapide, durable et mesurable.'],
                    ] as [$num, $icon, $title, $text])
                        <article class="bg-white p-8">
                            <div class="flex items-center justify-between">
                                <span class="icon-tile"><x-site.icon :name="$icon" class="h-5 w-5" /></span>
                                <span class="font-display text-3xl font-semibold text-sand-300">{{ $num }}</span>
                            </div>
                            <h3 class="mt-8 text-lg font-semibold text-ink-900">{{ $title }}</h3>
                            <p class="mt-3 text-sm leading-relaxed text-ink-600">{{ $text }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ── Logiciels ────────────────────────────────────────────────────── --}}
    <section id="solutions" class="py-24">
        <div class="site-container">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div class="max-w-2xl">
                    <p class="eyebrow">Nos logiciels</p>
                    <h2 class="display mt-5 text-4xl">Des outils métier pour votre transformation.</h2>
                    <p class="mt-5 text-ink-600">Conçus pour les réalités des entreprises d’Afrique de l’Ouest. D’autres solutions arrivent prochainement.</p>
                </div>
                <a href="{{ route('company.solutions') }}" class="link-arrow shrink-0">Toutes les solutions <x-site.icon name="arrow-right" class="h-4 w-4" /></a>
            </div>

            <div class="mt-14 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                <article class="card card-hover flex flex-col">
                    <div class="flex items-center justify-between">
                        <span class="badge badge-available">Disponible</span>
                        <span class="icon-tile"><x-site.icon name="beaker" class="h-5 w-5" /></span>
                    </div>
                    <h3 class="display mt-6 text-3xl">DMS</h3>
                    <p class="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-ink-500">Gestion des pharmacies</p>
                    <p class="mt-4 flex-1 text-sm leading-relaxed text-ink-600">Gestion officinale complète : commandes, stock, facturation, assurances mutuelles et tableau de bord.</p>
                    <div class="mt-7 flex flex-wrap gap-2">
                        <a href="{{ route('dms.presentation') }}" class="btn btn-primary btn-sm">Voir la présentation</a>
                        <a href="{{ route('company.presentation', ['intent' => 'Demande démo DMS']) }}#contact" class="btn btn-outline btn-sm">Demander une démo</a>
                    </div>
                </article>

                <article class="card card-hover flex flex-col">
                    <div class="flex items-center justify-between">
                        <span class="badge badge-soon">Bientôt</span>
                        <span class="icon-tile"><x-site.icon name="squares" class="h-5 w-5" /></span>
                    </div>
                    <h3 class="display mt-6 text-3xl">ERP</h3>
                    <p class="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-ink-500">Gestion des ressources d’entreprise</p>
                    <p class="mt-4 flex-1 text-sm leading-relaxed text-ink-600">Finances, facturation, projets, achats et reporting — une plateforme unifiée pour piloter toute votre activité.</p>
                    <div class="mt-7">
                        <a href="{{ route('company.presentation', ['intent' => 'Implémentation ERP']) }}#contact" class="btn btn-outline btn-sm">Nous contacter</a>
                    </div>
                </article>

                <article class="flex flex-col rounded-[1.25rem] border border-dashed border-sand-300 bg-sand-100/60 p-7">
                    <div class="flex items-center justify-between">
                        <span class="badge badge-muted">En développement</span>
                        <span class="icon-tile bg-sand-200 text-ink-500"><x-site.icon name="cube" class="h-5 w-5" /></span>
                    </div>
                    <h3 class="display mt-6 text-3xl text-ink-700">Prochainement</h3>
                    <p class="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-ink-500">Nouveaux logiciels métier</p>
                    <p class="mt-4 flex-1 text-sm leading-relaxed text-ink-600">De nouvelles solutions sectorielles sont en cours de développement. Laissez-nous vos coordonnées pour être informé en priorité.</p>
                    <div class="mt-7">
                        <a href="{{ route('company.presentation', ['intent' => 'Autre Enquête']) }}#contact" class="btn btn-outline btn-sm">Rester informé</a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- ── Contexte ─────────────────────────────────────────────────────── --}}
    <section class="bg-bogolan-dark py-24 text-sand-50">
        <div class="site-container grid gap-14 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <p class="eyebrow eyebrow-light">Conçu ici, pour ici</p>
                <h2 class="display mt-5 text-4xl text-sand-50 sm:text-5xl">Pensé pour le contexte ouest-africain.</h2>
                <p class="mt-6 max-w-md leading-relaxed text-sand-200/80">
                    Nos équipes connaissent le terrain : connectivité variable, réglementations locales et pratiques commerciales spécifiques.
                </p>
                <a href="{{ route('company.about') }}" class="btn btn-outline-on-dark mt-9">En savoir plus sur nous</a>
            </div>
            <div class="grid gap-x-10 gap-y-10 sm:grid-cols-2 lg:col-span-7">
                @foreach ([
                    ['signal', 'Connectivité adaptative', 'Optimisé pour fonctionner fluidement même avec une bande passante limitée.'],
                    ['globe', 'Expertise locale', 'Conformité avec les régulations régionales et les pratiques commerciales du terrain.'],
                    ['lock', 'Sécurité & conformité', 'Vos données restent protégées selon les standards internationaux.'],
                    ['wrench', 'Support réactif', 'Une équipe locale disponible pour accompagner vos équipes au quotidien.'],
                ] as [$icon, $title, $text])
                    <div class="border-t border-sand-50/15 pt-6">
                        <span class="icon-tile icon-tile-dark"><x-site.icon :name="$icon" class="h-5 w-5" /></span>
                        <h3 class="mt-5 text-lg font-semibold text-sand-50">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-sand-200/75">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── Contact ──────────────────────────────────────────────────────── --}}
    <section id="contact" class="scroll-mt-24 py-24">
        <div class="site-container grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <p class="eyebrow">Parlons-en</p>
                <h2 class="display mt-5 text-4xl">Établissons votre fondation.</h2>
                <p class="mt-5 leading-relaxed text-ink-600">Prenez contact avec nos architectes de solutions pour discuter de la transformation de vos opérations.</p>
                <dl class="mt-10 space-y-5 text-sm">
                    @foreach ([
                        ['map-pin', 'Adresse', $companyAddress ?: 'Bamako, Mali'],
                        ['mail', 'E-mail', $companyEmail],
                        ['phone', 'Téléphone', $companyPhone],
                        ['link', 'Site web', $companyWebsite],
                    ] as [$icon, $label, $value])
                        @if (filled($value))
                            <div class="flex items-start gap-4">
                                <span class="icon-tile h-10 w-10"><x-site.icon :name="$icon" class="h-4.5 w-4.5" /></span>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-500">{{ $label }}</dt>
                                    <dd class="mt-1 font-medium text-ink-900">{{ $value }}</dd>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </div>
            <div class="lg:col-span-7">
                <div class="card p-7 sm:p-10">
                    <x-site.contact-form default="Demande démo DMS" />
                </div>
            </div>
        </div>
    </section>
@endsection
